#!/usr/bin/env bash
protoc --php_out=. --grpc_out=. --plugin=protoc-gen-grpc=/usr/local/bin/grpc_php_plugin apibeat.proto