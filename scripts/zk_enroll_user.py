#!/usr/bin/env python3
import argparse
import json
import os
import sys
import traceback


PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
PYZK_ROOT = os.environ.get(
    "ZK_PYZK_ROOT",
    PROJECT_ROOT,
)

if PYZK_ROOT not in sys.path:
    sys.path.insert(0, PYZK_ROOT)

from zk import ZK, const  # noqa: E402


def parse_args():
    parser = argparse.ArgumentParser(description="Add/update user and trigger fingerprint enrollment")
    parser.add_argument("--ip", required=True, help="Device IP address")
    parser.add_argument("--port", type=int, default=4370, help="Device port")
    parser.add_argument("--password", type=int, default=0, help="Communication password")
    parser.add_argument("--timeout", type=int, default=15, help="Socket timeout in seconds")
    parser.add_argument("--user-id", required=True, help="Device user_id / PIN")
    parser.add_argument("--name", required=True, help="User display name")
    parser.add_argument("--privilege", type=int, default=0, help="Privilege level")
    parser.add_argument("--user-password", default="", help="Optional device user password")
    parser.add_argument("--card", type=int, default=0, help="Optional card number")
    parser.add_argument("--fid", type=int, default=0, help="Finger slot 0..9")
    parser.add_argument("--force-udp", action="store_true", help="Force UDP transport")
    return parser.parse_args()


def normalize_identifier(value):
    return str(value or "").strip()


def resolve_target_uid(users, user_id):
    target = next(
        (user for user in users if normalize_identifier(getattr(user, "user_id", "")) == user_id),
        None,
    )

    if target is not None:
        return int(target.uid), True

    next_uid = 1
    for user in users:
        next_uid = max(next_uid, int(getattr(user, "uid", 0)) + 1)

    return next_uid, False


def main():
    args = parse_args()
    conn = None

    try:
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

        try:
            conn.set_sdk_build_1()
        except Exception:
            pass

        user_id = normalize_identifier(args.user_id)
        users = conn.get_users() or []
        target_uid, existing = resolve_target_uid(users, user_id)

        conn.set_user(
            uid=target_uid,
            name=args.name.strip(),
            privilege=args.privilege if args.privilege in [const.USER_DEFAULT, const.USER_ADMIN, 14] else 0,
            password=args.user_password.strip(),
            group_id='',
            user_id=user_id,
            card=args.card,
        )

        try:
            conn.delete_user_template(target_uid, args.fid)
        except Exception:
            pass

        conn.reg_event(0xFFFF)
        enrolled = conn.enroll_user(target_uid, args.fid)
        template = conn.get_user_template(target_uid, args.fid) if enrolled else None

        print(
            json.dumps(
                {
                    "status": "ok",
                    "ip": args.ip,
                    "port": args.port,
                    "serial": conn.get_serialnumber(),
                    "user_id": user_id,
                    "uid": target_uid,
                    "fid": args.fid,
                    "existing_user": existing,
                    "enrolled": bool(enrolled),
                    "template_captured": template is not None,
                    "message": "Enrollment complete." if enrolled else "Enrollment command sent but no template was captured.",
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
