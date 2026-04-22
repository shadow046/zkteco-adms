#!/usr/bin/env python3
import argparse
import json
import os
import sys
import traceback


PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
PYZK_ROOT = os.environ.get(
    "ZK_PYZK_ROOT",
    os.path.abspath(os.path.join(PROJECT_ROOT, "..")),
)

if PYZK_ROOT not in sys.path:
    sys.path.insert(0, PYZK_ROOT)

from zk import ZK  # noqa: E402


def parse_args():
    parser = argparse.ArgumentParser(description="Direct ZKTeco attendance log query bridge")
    parser.add_argument("--ip", required=True, help="Device IP address")
    parser.add_argument("--port", type=int, default=4370, help="Device port")
    parser.add_argument("--password", type=int, default=0, help="Communication password")
    parser.add_argument("--timeout", type=int, default=15, help="Socket timeout in seconds")
    parser.add_argument("--limit", type=int, default=300, help="Maximum attendance rows to request")
    parser.add_argument("--force-udp", action="store_true", help="Force UDP transport")
    return parser.parse_args()


def normalize_user_id(value):
    return str(value or "").strip()


def attendance_to_payload(attendance):
    timestamp = getattr(attendance, "timestamp", None)

    return {
        "uid": int(getattr(attendance, "uid", 0) or 0),
        "user_id": normalize_user_id(getattr(attendance, "user_id", "")),
        "status": str(getattr(attendance, "status", "") or ""),
        "punch": str(getattr(attendance, "punch", "") or ""),
        "timestamp": timestamp.strftime("%Y-%m-%d %H:%M:%S") if timestamp is not None else None,
    }


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

        records = conn.get_attendance(last_records=max(1, args.limit)) or []

        print(
            json.dumps(
                {
                    "status": "ok",
                    "ip": args.ip,
                    "port": args.port,
                    "serial": conn.get_serialnumber(),
                    "attendance_count": len(records),
                    "records": [attendance_to_payload(record) for record in records],
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
