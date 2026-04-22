# Changelog

All notable changes to `shadow046/zkteco-adms` will be documented in this file.

## [0.1.0] - 2026-04-22
### Added
- Initial Laravel package scaffold for ZKTeco ADMS endpoints.
- ADMS core service for ATTLOG, OPERLOG, USERINFO, FINGERTMP, photo ingest, device state, and command queue.
- Package migrations for ADMS support tables.
- Default `inout_raw` migration for host apps without an attendance table.
- Built-in `DtrPairingService` with chronology checks, next-day carry-over, and manual `*` protection.
- Automatic DTR pairing listener after attendance ingest.
- Install command, config publishing, and test scaffold.
