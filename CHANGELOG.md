# Changelog

All notable changes to `dwoodard/neo4j-eloquent` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial package structure and core functionality
- Schema-less Node operations with `Node::label()` interface
- Fluent query builder with `where()`, `limit()`, `orderBy()` methods
- Multi-label node support
- Relationship traversal with `outgoing()`, `incoming()`, `related()` methods
- Laravel service provider with auto-discovery
- Comprehensive Pest test suite (13 tests)
- Neo4j connection service with Laudis client integration
- Configuration file with publishing support
- UUID generation for nodes
- Raw Cypher query execution support

### Features

- **Schema-less Operations**: Create any node type without predefined models
- **Dynamic Node Types**: Handle arbitrary labels and properties
- **Eloquent-style API**: Familiar Laravel query patterns
- **Multi-label Support**: Nodes can have multiple labels
- **Relationship Traversal**: Fluent relationship navigation
- **Laravel Integration**: Seamless service provider integration

## [1.0.0] - TBD

### Added

- Initial release
- Core Neo4j Eloquent functionality
