# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] 2025-03-23

### Changed

- Use `DateTimeImmutable` instead of `DateTimeInterface` in all signatures.
- Disconnect client in destructor if PHP >= 8.4.
- Do not throw in `Client::disconnect()` if client is not connected.

### Fixed

- Allow to use `Confirmation::awaitAll()` without iteration.
