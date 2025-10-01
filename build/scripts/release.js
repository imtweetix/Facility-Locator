const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

class ReleaseBuilder {
  constructor() {
    this.rootDir = path.resolve(__dirname, '../..');
    this.distDir = path.join(this.rootDir, 'dist');
    this.releaseDir = path.join(this.rootDir, 'release');
    this.tempDir = path.join(this.releaseDir, 'temp');

    this.includeFiles = [
      'facility-locator.php',
      'readme.txt',
      'uninstall.php',
      'languages/**/*',
      'includes/**/*',
      'admin/**/*.php',
      'public/**/*.php',
      'templates/**/*',
      'dist/**/*'
    ];

    this.excludePatterns = [
      'src/**/*',
      'build/**/*',
      'tests/**/*',
      'node_modules/**/*',
      'vendor/**/*',
      '.git/**/*',
      '.github/**/*',
      '.vscode/**/*',
      '*.log',
      '.DS_Store',
      'Thumbs.db',
      'package*.json',
      'webpack.config.js',
      '.babelrc',
      '.eslintrc*',
      'composer.*',
      '.gitignore',
      '.gitattributes'
    ];
  }

  async build() {
    console.log('🚀 Starting release build process...');

    try {
      this.validateEnvironment();
      this.cleanReleaseDirectory();
      this.createReleaseDirectory();
      this.copyReleaseFiles();
      this.updatePluginVersion();
      this.createZipArchive();
      this.cleanup();

      console.log('✅ Release build completed successfully!');
      console.log(`📦 Release package: ${path.join(this.releaseDir, 'facility-locator.zip')}`);

    } catch (error) {
      console.error('❌ Release build failed:', error.message);
      process.exit(1);
    }
  }

  validateEnvironment() {
    console.log('🔍 Validating environment...');

    // Check if dist directory exists
    if (!fs.existsSync(this.distDir)) {
      throw new Error('Dist directory not found. Run "npm run build" first.');
    }

    // Check if main plugin file exists
    const mainFile = path.join(this.rootDir, 'facility-locator.php');
    if (!fs.existsSync(mainFile)) {
      throw new Error('Main plugin file not found: facility-locator.php');
    }

    // Check Node.js version
    const nodeVersion = process.version;
    const majorVersion = parseInt(nodeVersion.slice(1).split('.')[0]);
    if (majorVersion < 16) {
      throw new Error('Node.js 16 or higher is required for the build process.');
    }

    console.log('✅ Environment validation passed');
  }

  cleanReleaseDirectory() {
    console.log('🧹 Cleaning release directory...');

    if (fs.existsSync(this.releaseDir)) {
      fs.rmSync(this.releaseDir, { recursive: true, force: true });
    }
  }

  createReleaseDirectory() {
    console.log('📁 Creating release directory structure...');

    fs.mkdirSync(this.releaseDir, { recursive: true });
    fs.mkdirSync(this.tempDir, { recursive: true });
  }

  copyReleaseFiles() {
    console.log('📋 Copying release files...');

    const targetDir = path.join(this.tempDir, 'facility-locator');
    fs.mkdirSync(targetDir, { recursive: true });

    // Copy all required files
    this.copyDirectory(this.rootDir, targetDir);

    console.log('✅ Files copied successfully');
  }

  copyDirectory(src, dest) {
    const items = fs.readdirSync(src);

    for (const item of items) {
      const srcPath = path.join(src, item);
      const destPath = path.join(dest, item);
      const stat = fs.statSync(srcPath);

      // Skip excluded patterns
      if (this.shouldExclude(path.relative(this.rootDir, srcPath))) {
        continue;
      }

      if (stat.isDirectory()) {
        fs.mkdirSync(destPath, { recursive: true });
        this.copyDirectory(srcPath, destPath);
      } else {
        fs.copyFileSync(srcPath, destPath);
      }
    }
  }

  shouldExclude(relativePath) {
    return this.excludePatterns.some(pattern => {
      // Convert glob patterns to regex
      const regexPattern = pattern
        .replace(/\*\*/g, '.*')
        .replace(/\*/g, '[^/]*')
        .replace(/\?/g, '[^/]');

      return new RegExp(`^${regexPattern}$`).test(relativePath);
    });
  }

  updatePluginVersion() {
    console.log('🔄 Updating plugin version for production...');

    const pluginFile = path.join(this.tempDir, 'facility-locator', 'facility-locator.php');
    let content = fs.readFileSync(pluginFile, 'utf8');

    // Remove (DEV) from plugin name for production
    content = content.replace(
      /Plugin Name:\s*Facility Locator \(DEV\)/,
      'Plugin Name: Facility Locator'
    );

    // Ensure version consistency
    const version = this.getVersionFromPackageJson();
    content = content.replace(
      /Version:\s*[\d.]+/,
      `Version: ${version}`
    );
    content = content.replace(
      /define\('FACILITY_LOCATOR_VERSION',\s*'[\d.]+'\);/,
      `define('FACILITY_LOCATOR_VERSION', '${version}');`
    );

    fs.writeFileSync(pluginFile, content);

    console.log(`✅ Plugin version updated to ${version}`);
  }

  getVersionFromPackageJson() {
    const packageJsonPath = path.join(this.rootDir, 'package.json');
    const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
    return packageJson.version;
  }

  createZipArchive() {
    console.log('📦 Creating ZIP archive...');

    try {
      // Use native zip command if available, otherwise fall back to cross-platform solution
      const zipPath = path.join(this.releaseDir, 'facility-locator.zip');
      const facilityLocatorDir = path.join(this.tempDir, 'facility-locator');

      // Try to use system zip command
      try {
        execSync(`cd "${this.tempDir}" && zip -r "../facility-locator.zip" "facility-locator"`, {
          stdio: 'pipe'
        });
        console.log('✅ ZIP archive created using system zip command');
      } catch (error) {
        // Fallback to cross-platform solution
        this.createZipArchiveFallback(facilityLocatorDir, zipPath);
        console.log('✅ ZIP archive created using fallback method');
      }

      // Verify zip file was created
      if (!fs.existsSync(zipPath)) {
        throw new Error('ZIP archive was not created successfully');
      }

      const stats = fs.statSync(zipPath);
      console.log(`📊 Archive size: ${(stats.size / 1024 / 1024).toFixed(2)} MB`);

    } catch (error) {
      throw new Error(`Failed to create ZIP archive: ${error.message}`);
    }
  }

  createZipArchiveFallback(sourceDir, zipPath) {
    // Simple fallback - could be enhanced with archiver library
    console.log('Using fallback ZIP creation method...');

    // This is a simplified version - in production you might want to use the 'archiver' npm package
    execSync(`cd "${path.dirname(sourceDir)}" && tar -czf "${zipPath.replace('.zip', '.tar.gz')}" "${path.basename(sourceDir)}"`, {
      stdio: 'pipe'
    });

    console.log('Created .tar.gz archive as fallback');
  }

  cleanup() {
    console.log('🧹 Cleaning up temporary files...');

    if (fs.existsSync(this.tempDir)) {
      fs.rmSync(this.tempDir, { recursive: true, force: true });
    }

    console.log('✅ Cleanup completed');
  }
}

// Run the build process
if (require.main === module) {
  const builder = new ReleaseBuilder();
  builder.build().catch(error => {
    console.error('Build failed:', error);
    process.exit(1);
  });
}

module.exports = ReleaseBuilder;