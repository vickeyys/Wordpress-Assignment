pipeline {
  agent any

  environment {
    BASE_DIR   = "wp-saas-platform"
    THEME_DIR  = "wp-saas-platform/theme"
    PLUGIN_DIR = "wp-saas-platform/plugin"
  }

  stages {

    // =====================================================
    // 1) PULL REQUEST QUALITY GATE
    // =====================================================
    stage('PR – Code Quality Checks') {
      when { changeRequest() }

      steps {
        echo "Running WordPress SaaS PR checks..."

        sh '''
          echo "PHP syntax validation..."
          find ${THEME_DIR} ${PLUGIN_DIR} -name "*.php" -exec php -l {} \\;

          echo "Basic security scan..."
          ! grep -R "eval(" ${THEME_DIR} ${PLUGIN_DIR}
          ! grep -R "base64_decode" ${THEME_DIR} ${PLUGIN_DIR}

          echo "PR checks passed"
        '''
      }
    }

    // =====================================================
    // 2) BUILD VERSIONED ARTIFACTS
    // =====================================================
    stage('Build SaaS Release Artifacts') {
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
          echo "Building SaaS release version: ${VERSION}"
          mkdir -p artifacts

          zip -r artifacts/theme-${VERSION}.zip ${THEME_DIR}
          zip -r artifacts/plugin-${VERSION}.zip ${PLUGIN_DIR}
        '''
      }
    }

    // =====================================================
    // 3) SIMULATED DEPLOYMENT (Assignment requirement)
    // =====================================================
    stage('Simulate Deployment to Tenants') {
      when { branch 'main' }

      steps {
        echo """
        Simulated SaaS deployment:

        - theme-${VERSION}.zip
        - plugin-${VERSION}.zip

        In production:
        These artifacts would be copied into:
        EFS → tenants → wp-content → themes/plugins
        which are mounted by all tenant WordPress containers.
        """
      }
    }

    // =====================================================
    // 4) ROLLBACK SUPPORT
    // =====================================================
    stage('Archive Release for Rollback') {
      when { branch 'main' }

      steps {
        archiveArtifacts artifacts: 'artifacts/*.zip', fingerprint: true
      }
    }
  }
}
