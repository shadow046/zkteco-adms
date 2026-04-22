#!/usr/bin/env python3
import argparse
import json
import os
import sys
import traceback
from collections import defaultdict


PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
PYZK_ROOT = os.environ.get(
    "ZK_PYZK_ROOT",
    os.path.abspath(os.path.join(PROJECT_ROOT, "..")),
)

if PYZK_ROOT not in sys.path:
    sys.path.insert(0, PYZK_ROOT)

from zk import ZK  # noqa: E402
from zk.user import User  # noqa: E402
from zk.finger import Finger  # noqa: E402


def parse_args():
    parser = argparse.ArgumentParser(description="Safe ZKTeco template restore bridge")
    parser.add_argument("--ip", required=True, help="Device IP address")
    parser.add_argument("--port", type=int, default=4370, help="Device port")
    parser.add_argument("--password", type=int, default=0, help="Communication password")
    parser.add_argument("--timeout", type=int, default=15, help="Socket timeout in seconds")
    parser.add_argument("--backup", required=True, help="Backup JSON path")
    parser.add_argument("--user-id", help="Restore only one backup user_id / PIN")
    parser.add_argument("--force-udp", action="store_true", help="Force UDP transport")
    return parser.parse_args()


def normalize_user_id(user):
    user_id = str(getattr(user, "user_id", "") or "").strip()
    if user_id:
        return user_id

    uid = getattr(user, "uid", None)
    return "" if uid is None else str(uid).strip()


def build_existing_user_maps(users):
    by_user_id = {}
    used_uids = set()

    for user in users:
        used_uids.add(int(user.uid))
        user_id = normalize_user_id(user)
        if user_id:
            by_user_id[user_id] = user

    return by_user_id, used_uids


def next_available_uid(used_uids):
    candidate = 1
    while candidate in used_uids:
        candidate += 1
    used_uids.add(candidate)
    return candidate


def normalize_value(value):
    return str(value or "").strip()


def main():
    args = parse_args()
    conn = None

    try:
        with open(args.backup, "r", encoding="utf-8") as infile:
            data = json.load(infile)

        backup_fp_version = str(data.get("fp_version", "") or "").strip()
        backup_users = [User.json_unpack(item) for item in (data.get("users") or [])]
        backup_fingers = [Finger.json_unpack(item) for item in (data.get("templates") or [])]
        backup_fingers_by_uid = defaultdict(list)
        requested_user_id = normalize_value(args.user_id)

        for finger in backup_fingers:
            backup_fingers_by_uid[int(finger.uid)].append(finger)

        if requested_user_id:
            filtered_users = [
                user for user in backup_users
                if normalize_value(getattr(user, "user_id", "")) == requested_user_id
                or normalize_value(getattr(user, "uid", "")) == requested_user_id
            ]

            if not filtered_users:
                raise RuntimeError(f"User ID/PIN {requested_user_id} not found in backup file.")

            allowed_uids = {int(user.uid) for user in filtered_users}
            backup_users = filtered_users
            backup_fingers = [finger for finger in backup_fingers if int(finger.uid) in allowed_uids]
            backup_fingers_by_uid = defaultdict(list)

            for finger in backup_fingers:
                backup_fingers_by_uid[int(finger.uid)].append(finger)

        zk = ZK(
            args.ip,
            port=args.port,
            timeout=args.timeout,
            password=args.password,
            force_udp=args.force_udp,
            ommit_ping=False,
        )
        conn = zk.connect()
        conn.disable_device()

        device_serial = conn.get_serialnumber()
        device_fp_version = str(conn.get_fp_version() or "").strip()

        if backup_fp_version and device_fp_version and backup_fp_version != device_fp_version:
            raise RuntimeError(
                f"Fingerprint version mismatch: backup={backup_fp_version}, device={device_fp_version}"
            )

        existing_users = conn.get_users() or []
        existing_by_user_id, used_uids = build_existing_user_maps(existing_users)

        restored_users = 0
        restored_templates = 0
        users_without_templates = 0
        remapped_users = 0

        for backup_user in backup_users:
            backup_uid = int(backup_user.uid)
            backup_user_id = normalize_user_id(backup_user)
            existing_user = existing_by_user_id.get(backup_user_id) if backup_user_id else None

            if existing_user is not None:
                target_uid = int(existing_user.uid)
            elif backup_uid not in used_uids:
                target_uid = backup_uid
                used_uids.add(target_uid)
            else:
                target_uid = next_available_uid(used_uids)
                remapped_users += 1

            target_user = User(
                uid=target_uid,
                name=backup_user.name,
                privilege=backup_user.privilege,
                password=backup_user.password,
                group_id=backup_user.group_id,
                user_id=backup_user.user_id,
                card=backup_user.card,
            )

            target_fingers = [
                Finger(uid=target_uid, fid=int(finger.fid), valid=int(finger.valid), template=finger.template)
                for finger in backup_fingers_by_uid.get(backup_uid, [])
            ]

            if target_fingers:
                conn.save_user_template(target_user, target_fingers)
                restored_templates += len(target_fingers)
            else:
                conn.set_user(
                    uid=target_user.uid,
                    name=target_user.name,
                    privilege=target_user.privilege,
                    password=target_user.password,
                    group_id=target_user.group_id,
                    user_id=target_user.user_id,
                    card=target_user.card,
                )
                users_without_templates += 1

            if backup_user_id:
                existing_by_user_id[backup_user_id] = target_user
            restored_users += 1

        print(
            json.dumps(
                {
                    "status": "ok",
                    "ip": args.ip,
                    "port": args.port,
                    "serial": device_serial,
                    "fp_version": device_fp_version,
                    "restored_users": restored_users,
                    "restored_templates": restored_templates,
                    "users_without_templates": users_without_templates,
                    "remapped_users": remapped_users,
                    "restored_user_id": requested_user_id or None,
                    "backup_file": os.path.basename(args.backup),
                    "mode": "single_user_restore" if requested_user_id else "merge_restore",
                },
                ensure_ascii=True,
            )
        )
    except Exception as exc:  # pragma: no cover - runtime bridge
        message = str(exc).strip() or exc.__class__.__name__
        sys.stderr.write(message + "\n")
        if os.environ.get("ZK_BACKUP_DEBUG") == "1":
            traceback.print_exc(file=sys.stderr)
        sys.exit(1)
    finally:
        if conn is not None:
            try:
                conn.enable_device()
            except Exception:
                pass
            try:
                conn.disconnect()
            except Exception:
                pass


if __name__ == "__main__":
    main()
