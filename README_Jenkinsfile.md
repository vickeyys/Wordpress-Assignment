# Jenkinsfile Documentation

## Overview

This Jenkinsfile implements a comprehensive CI/CD pipeline for a **WordPress SaaS (Software as a Service) Platform**. The pipeline automates the entire software delivery lifecycle for a multi-tenant WordPress application, from code quality checks to deployment preparation.

## Project Context

The pipeline is designed for the `wp-saas-platform` project, which appears to be a containerized WordPress SaaS solution with:
- Multiple tenant WordPress instances (tenant1, tenant2)
- Nginx reverse proxy for routing
- MySQL databases per tenant
- Monitoring stack (Prometheus, Grafana, NGINX Exporter)
- Docker-based infrastructure

## Pipeline Architecture

### Pipeline Type
- **Declarative Pipeline**: Uses Jenkins' declarative syntax for better readability and maintainability
- **Agent**: `agent any` - Can run on any available Jenkins agent

### Environment Variables
```groovy
BASE_DIR   = "wp-saas-platform"      // Root directory of the SaaS platform
THEME_DIR  = "wp-saas-platform/theme"    // WordPress theme directory
PLUGIN_DIR = "wp-saas-platform/plugin"   // WordPress plugin directory
```

## Pipeline Stages

### 1. PR – Code Quality Checks
**Trigger**: Pull Requests only (`when { changeRequest() }`)

**Purpose**: Quality gate for code changes before merging

**Checks Performed**:
- **PHP Syntax Validation**: Validates all PHP files in theme and plugin directories
- **Basic Security Scan**:
  - Scans for dangerous functions like `eval()`
  - Scans for `base64_decode()` usage
- **Output**: "PR checks passed" confirmation

**Why This Stage**:
- Prevents broken PHP code from entering main branch
- Basic security validation for WordPress code
- Ensures code quality standards are met

### 2. Build SaaS Release Artifacts
**Trigger**: Main branch OR Git tags (`buildingTag()`)

**Purpose**: Create versioned, deployable artifacts

**Versioning Logic**:
- If triggered by Git tag: Uses `GIT_TAG_NAME` as version
- If triggered by main branch push: Uses `build-${BUILD_NUMBER}` format

**Artifacts Created**:
- `theme-${VERSION}.zip`: Zipped WordPress theme
- `plugin-${VERSION}.zip`: Zipped WordPress plugin

**Directory Structure**:
```
artifacts/
├── theme-v1.0.0.zip    # or theme-build-123.zip
└── plugin-v1.0.0.zip   # or plugin-build-123.zip
```

### 3. Simulate Deployment to Tenants
**Trigger**: Main branch only (`when { branch 'main' }`)

**Purpose**: Demonstrate deployment process (Assignment requirement)

**What it does**:
- Echoes deployment simulation information
- Shows which artifacts would be deployed
- Explains production deployment path

**Production Deployment Path**:
```
EFS (Elastic File System)
├── tenants/
│   ├── tenant1/
│   │   └── wp-content/
│   │       ├── themes/  ← theme.zip extracted here
│   │       └── plugins/ ← plugin.zip extracted here
│   └── tenant2/
│       └── wp-content/
│           ├── themes/
│           └── plugins/
```

### 4. Archive Release for Rollback
**Trigger**: Main branch only

**Purpose**: Enable rollback capability

**Actions**:
- Archives all `artifacts/*.zip` files
- Creates fingerprints for artifact tracking
- Stores artifacts in Jenkins for future rollbacks

## Trigger Conditions

### Pull Requests
- Runs only on pull requests
- Performs code quality checks
- No artifacts created

### Main Branch Push
- Runs full pipeline (quality checks + build + deploy simulation + archive)
- Creates versioned artifacts
- Prepares for deployment

### Git Tags
- Runs build stage only
- Creates tag-specific versioned artifacts
- Useful for releases

## Security Considerations

### Code Security
- Scans for dangerous PHP functions (`eval`, `base64_decode`)
- Validates PHP syntax
- Prevents potentially malicious code from deployment

### Access Control
- Pipeline stages have specific trigger conditions
- Artifacts are fingerprinted for integrity
- Rollback capability for quick recovery

## Rollback Strategy

The pipeline supports rollback through:
1. **Archived Artifacts**: Previous builds stored in Jenkins
2. **Version Control**: Git tags for specific releases
3. **Deployment Path**: EFS-mounted directories allow quick reversion

## Production Deployment Notes

In a real production environment, the "Simulate Deployment" stage would be replaced with:
- Actual file copying to EFS
- Container restarts (if needed)
- Database migrations (if required)
- Health checks post-deployment
- Notification systems (Slack, email, etc.)

## Usage Examples

### Typical Development Workflow
1. Developer creates pull request with WordPress theme/plugin changes
2. Jenkins runs code quality checks
3. After PR merge to main, full pipeline executes
4. Versioned artifacts created and archived
5. Deployment simulation shows what would happen in production

### Release Workflow
1. Create Git tag (e.g., `v1.2.3`)
2. Jenkins builds tag-specific artifacts
3. Artifacts can be manually deployed or used in production pipelines

## Dependencies

### Required Jenkins Plugins
- Pipeline (declarative)
- Git
- Archive Artifacts

### System Requirements
- Linux/Unix agent (for shell scripts)
- PHP CLI (for syntax validation)
- Zip utility
- Git client

## Monitoring & Maintenance

### Pipeline Health
- Monitor build success/failure rates
- Review archived artifacts for size and content
- Check execution times for performance issues

### Troubleshooting
- Check Jenkins logs for stage failures
- Verify PHP syntax errors
- Ensure proper directory structure in workspace

## Assignment Context

This Jenkinsfile appears to be part of a DevOps assignment demonstrating:
- CI/CD pipeline design
- Multi-tenant application deployment
- WordPress-specific considerations
- Container orchestration integration
- Monitoring and logging setup

The pipeline successfully demonstrates automated quality assurance, artifact management, and deployment preparation for a SaaS WordPress platform.
