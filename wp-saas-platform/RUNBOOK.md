# 🧰 WordPress SaaS Platform Runbook

> **Audience**: On-call DevOps engineers and platform operators
>
> **Purpose**: Operational procedures for monitoring, troubleshooting, and recovering the multi-tenant WordPress SaaS platform

---

## 📊 Platform Overview

### 🏗️ System Components
| Component | Purpose | Count |
|-----------|---------|-------|
| **🏗️ NGINX Reverse Proxy** | Route traffic to tenants | 1 instance |
| **🚀 WordPress Containers** | Tenant applications | 2 tenants |
| **🐬 MySQL Databases** | Tenant data storage | 2 databases |
| **🔄 Jenkins CI/CD** | Automated deployments | 1 instance |
| **📊 Monitoring Stack** | Prometheus + Grafana | 1 instance each |

### 🌐 Architecture Summary
- **Single Entry Point**: All tenant traffic flows through NGINX
- **Container Isolation**: Each tenant runs in separate containers
- **Data Isolation**: Per-tenant databases and volumes
- **Orchestration**: Docker Compose manages all services

---

## 🚨 Incident Response Procedures

### 1️⃣ 🔴 **CRITICAL: Tenant Website Down**

#### 🚩 Symptoms
- Tenant website returns `502`, `503`, or connection errors
- Users cannot access `tenantX.madsavanna.store`
- WordPress admin panel inaccessible

#### 🔍 Investigation Steps

**Step 1: Check NGINX Status**
```bash
# Verify NGINX container is running
docker compose ps nginx

# Check NGINX logs for errors
docker compose logs nginx --tail=50
```

**Step 2: Check WordPress Container**
```bash
# Verify WordPress container status
docker compose ps wp_tenant1

# Check WordPress application logs
docker compose logs wp_tenant1 --tail=50
```

**Step 3: Check Database Connectivity**
```bash
# Verify database container status
docker compose ps db_tenant1

# Check database logs
docker compose logs db_tenant1 --tail=50
```

#### ✅ Resolution
```bash
# Restart only the affected tenant (isolates impact)
docker compose restart wp_tenant1
```

> **⚠️ Impact**: Only affects the specific tenant. Other tenants remain operational.

---

### 2️⃣ 🟠 **HIGH: MySQL Database Issues**

#### 🚩 Symptoms
- WordPress shows: `"Error establishing database connection"`
- Tenant admin login fails
- Site displays database errors

#### 🔍 Investigation Steps
```bash
# Check database container status
docker compose ps db_tenant1

# Review database error logs
docker compose logs db_tenant1 --tail=50

# Test database connectivity (if accessible)
docker compose exec db_tenant1 mysql -u root -p -e "SELECT 1;"
```

#### ✅ Resolution
```bash
# Restart database container
docker compose restart db_tenant1
```

> **💾 Data Safety**: Database volumes persist across restarts. No data loss occurs.

---

### 3️⃣ 🟡 **MEDIUM: High Resource Usage**

#### 🚩 Symptoms
- Slow website performance
- High CPU/memory alerts in Grafana
- Other tenants experiencing degraded performance

#### 🔍 Investigation Steps
```bash
# Check all container resource usage
docker stats

# Identify resource-intensive containers
docker stats --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"

# Check cAdvisor (http://localhost:8081) for detailed metrics
# Check Grafana dashboards for historical trends
```

#### ✅ Resolution
```bash
# Restart the resource-intensive tenant
docker compose restart wp_tenant1

# Monitor resource usage post-restart
docker stats wp_tenant1
```

> **🛡️ Protection**: Prevents resource exhaustion from affecting other tenants.

---

### 4️⃣ 🟢 **LOW: CI/CD Deployment Failure**

#### 🚩 Symptoms
- Jenkins pipeline shows red/failure status
- No new theme/plugin deployments
- Build artifacts not generated

#### 🔍 Investigation Steps
```bash
# Access Jenkins UI (http://localhost:8080)
# Navigate to failed pipeline
# Review build logs for errors

# Common issues:
# - PHP syntax errors
# - Security scan failures
# - Missing dependencies
```

#### ✅ Resolution
```bash
# Fix code issues based on Jenkins logs
# Commit and push fixes
# Re-run Jenkins pipeline

# No production impact until successful build
```

---

## 🔄 Rollback Procedures

### 🎯 **Theme/Plugin Rollback**

#### When to Rollback
- New release causes functionality issues
- Theme/plugin breaks tenant websites
- User-reported problems after deployment

#### 📋 Rollback Steps

**Step 1: Access Jenkins**
```bash
# Open Jenkins UI: http://localhost:8080
# Navigate to successful builds history
```

**Step 2: Download Previous Artifacts**
```bash
# Download from last successful build:
# - theme-<previous-version>.zip
# - plugin-<previous-version>.zip
```

**Step 3: Deploy Previous Version**
```bash
# Extract into tenant directories
unzip theme-v1.0.0.zip -d tenants/tenant1/wp-content/themes/
unzip plugin-v1.0.0.zip -d tenants/tenant1/wp-content/plugins/

# For tenant2
unzip theme-v1.0.0.zip -d tenants/tenant2/wp-content/themes/
unzip plugin-v1.0.0.zip -d tenants/tenant2/wp-content/plugins/
```

**Step 4: Restart WordPress Containers**
```bash
# Restart affected tenants
docker compose restart wp_tenant1 wp_tenant2
```

> **✅ Result**: Tenants revert to previous stable version.

---

## 🛠️ Platform Maintenance

### 🔄 **Full Platform Restart**

#### When to Use
- Multiple services unstable
- Docker daemon issues
- System-wide resource problems

#### ⚠️ Safety Precautions
- **Data Preservation**: Docker volumes persist across restarts
- **Minimal Downtime**: All tenants restart simultaneously
- **Monitoring**: Watch for startup errors

#### 📋 Restart Procedure
```bash
# Graceful shutdown
docker compose down

# Wait for clean shutdown
sleep 10

# Restart all services
docker compose up -d

# Verify all services started
docker compose ps

# Check service health
curl -f http://localhost:9090/-/healthy
```

---

## 🔐 Backup & Recovery

### 💾 **Database Backup Strategy**

#### Storage Locations
- **Tenant 1**: `db_tenant1` Docker volume
- **Tenant 2**: `db_tenant2` Docker volume

#### 📋 Backup Procedure
```bash
#!/bin/bash
# Create database backups

# Tenant 1 backup
docker compose exec db_tenant1 mysqldump \
    -u root -p"$MYSQL_ROOT_PASSWORD" wordpress \
    > backup_tenant1_$(date +%Y%m%d_%H%M%S).sql

# Tenant 2 backup
docker compose exec db_tenant2 mysqldump \
    -u root -p"$MYSQL_ROOT_PASSWORD" wordpress \
    > backup_tenant2_$(date +%Y%m%d_%H%M%S).sql
```

#### 🔄 **Restore Procedure**
```bash
#!/bin/bash
# Restore database from backup

# Tenant 1 restore
docker compose exec -T db_tenant1 mysql \
    -u root -p"$MYSQL_ROOT_PASSWORD" wordpress \
    < backup_tenant1_20231201_120000.sql

# Tenant 2 restore
docker compose exec -T db_tenant2 mysql \
    -u root -p"$MYSQL_ROOT_PASSWORD" wordpress \
    < backup_tenant2_20231201_120000.sql
```

---

## 📊 Monitoring & Health Checks

### 🔍 **Key Monitoring Points**

| Component | Health Check | Alert Threshold |
|-----------|--------------|-----------------|
| **NGINX** | `docker compose ps nginx` | Container down |
| **WordPress** | HTTP 200 response | 5xx errors |
| **MySQL** | `SELECT 1` query | Connection failures |
| **Jenkins** | Pipeline status | Build failures |
| **Prometheus** | `/health` endpoint | Scraping failures |

### 📈 **Grafana Dashboards**

Access dashboards at: `http://localhost:3000`
- **Container Resources**: CPU, Memory per tenant
- **NGINX Metrics**: Requests, response times
- **Database Performance**: Connections, slow queries
- **Application Health**: Error rates, response codes

---

## 🎯 Operational Principles

### 🛡️ **Tenant Isolation**
- **Principle**: One tenant must never affect another tenant
- **Practice**: Always restart individual tenants when possible
- **Benefit**: Minimizes blast radius of incidents

### 🔄 **Immutable Deployments**
- **Theme/Plugin Updates**: No container rebuilds required
- **Version Control**: Jenkins maintains rollback versions
- **Zero Downtime**: Updates via shared volumes

### 📊 **Observability First**
- **Monitoring**: Prometheus scrapes all components
- **Visualization**: Grafana provides operational insights
- **Alerting**: Automated notifications for critical issues

---

## 📞 Emergency Contacts

| Role | Contact | Availability |
|------|---------|--------------|
| **Primary On-call** | DevOps Engineer | 24/7 |
| **Platform Owner** | Tech Lead | Business Hours |
| **Infrastructure** | Cloud Team | 24/7 |

---

## 📝 Change Management

### 🏗️ **Platform Modifications**
1. **Test in Development**: Validate changes locally
2. **Update Documentation**: Modify runbook as needed
3. **Gradual Rollout**: Test with one tenant first
4. **Rollback Plan**: Ensure rollback procedures work

### 📊 **Monitoring Changes**
- Update Grafana dashboards for new metrics
- Modify Prometheus alerting rules
- Update health check procedures

---

## 🎯 Success Metrics

- **⏱️ MTTR**: Mean Time To Resolution < 15 minutes
- **📈 Uptime**: Platform availability > 99.9%
- **🛡️ Isolation**: Zero cross-tenant incidents
- **🔄 Rollbacks**: < 5 minutes to previous version

---

> **💡 Remember**: This platform is designed for **resilience** and **tenant isolation**. Always prefer per-tenant fixes over full platform restarts.