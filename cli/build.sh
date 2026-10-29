#!/bin/sh
# Compila la CLI para Windows, Linux y macOS en cli/dist (requiere Go 1.22 o superior).
# Uso: ./build.sh [versión]
set -e
cd "$(dirname "$0")"
VERSION="${1:-1.0.0}"
LDFLAGS="-s -w -X main.Version=$VERSION"
mkdir -p dist
GOOS=windows GOARCH=amd64 go build -ldflags "$LDFLAGS" -o dist/codeflow.exe .
GOOS=linux   GOARCH=amd64 go build -ldflags "$LDFLAGS" -o dist/codeflow-linux .
GOOS=darwin  GOARCH=arm64 go build -ldflags "$LDFLAGS" -o dist/codeflow-macos .
echo "Listo:"; ls -lh dist
