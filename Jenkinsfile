pipeline {
  agent any

  environment {
    BASE_DIR   = "wp-saas-platform"
    THEME_DIR  = "wp-saas-platform/theme"
    PLUGIN_DIR = "wp-saas-platform/plugin"
    TENANT1    = "wp-saas-platform/tenants/tenant1/wp-content"
    TENANT2    = "wp-saas-platform/tenants/tenant2/wp-content"
  }

  stages {

    // ============================
    // PR checks
    // ============================
    stage('PR Quality Checks') {
      when { changeRequest() }

      steps {
        echo "Running PHP + security checks on PR..."

        sh '''
          php -v

          find ${THEME_DIR} ${PLUGIN_DIR} -name "*.php" -exec php -l {} \\;

          ! grep -R "eval(" ${THEME_DIR} ${PLUGIN_DIR}
          ! grep -R "base64_decode" ${THEME_DIR} ${PLUGIN_DIR}
        '''
      }
    }

    // ============================
    // Build artifacts
    // ============================
    stage('Build Versioned Artifacts') {
      when {
        anyOf {
          branch 'main'
          buildingTag()
        }
      }

      steps {
        script {
          if (env.GIT_TAG_NAME) {
            env.VERSION = env.GIT_TAG_NAME
          } else {
            env.VERSION = "build-${env.BUILD_NUMBER}"
          }
        }

        sh '''
          mkdir -p artifacts
          zip -r artifacts/theme-${VERSION}.zip ${THEME_DIR}
          zip -r artifacts/plugin-${VERSION}.zip ${PLUGIN_DIR}
        '''
      }
    }

    // ============================
    // Deploy to tenants
    // ============================
    stage('Deploy to Tenants') {
      when { branch 'main' }

      steps {
        sh '''
          unzip -o artifacts/theme-${VERSION}.zip -d ${TENANT1}/themes
          unzip -o artifacts/theme-${VERSION}.zip -d ${TENANT2}/themes

          unzip -o artifacts/plugin-${VERSION}.zip -d ${TENANT1}/plugins
          unzip -o artifacts/plugin-${VERSION}.zip -d ${TENANT2}/plugins
        '''
      }
    }

    // ============================
    // Rollback support
    // ============================
    stage('Archive Artifacts') {
      when { branch 'main' }

      steps {
        archiveArtifacts artifacts: 'artifacts/*.zip', fingerprint: true
      }
    }
  }
}
