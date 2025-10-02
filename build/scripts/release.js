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
      'src',
      'src/**/*',
      'build',
      'build/**/*',
      'tests',
      'tests/**/*',
      'node_modules',
      'node_modules/**/*',
      'vendor',
      'vendor/**/*',
      'release',
      'release/**/*',
      'dist',
      'dist/**/*',
      '.git',
      '.git/**/*',
      '.github',
      '.github/**/*',
      '.vscode',
      '.vscode/**/*',
      '.claude',
      '.claude/**/*',
      '.claude-instructions',
      'git-cleanup-commands.md',
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
      this.copyDistAssets();
      this.updatePluginVersion();
      await this.createZipArchive();
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
    console.log('Release directory path:', this.releaseDir);

    // Safety check: ensure we're not deleting the entire project
    const releaseDirName = path.basename(this.releaseDir);
    if (releaseDirName !== 'release') {
      throw new Error(`Safety check failed: Expected to clean 'release' directory but got '${releaseDirName}'`);
    }

    // Additional safety check: ensure the release directory is within the project
    const relativePath = path.relative(this.rootDir, this.releaseDir);
    if (relativePath !== 'release') {
      throw new Error(`Safety check failed: Release directory is not in expected location. Got: ${relativePath}`);
    }

    if (fs.existsSync(this.releaseDir)) {
      console.log('Release directory exists, removing...');
      try {
        // Use Windows command directly on Windows platform
        if (process.platform === 'win32') {
          execSync(`cmd /c "rmdir /s /q "${this.releaseDir}""`, { stdio: 'pipe' });
          console.log('✅ Release directory cleaned using Windows cmd');
        } else {
          fs.rmSync(this.releaseDir, { recursive: true, force: true });
          console.log('✅ Release directory cleaned using fs.rmSync');
        }
      } catch (error) {
        console.log('Error cleaning release directory:', error.message);
        // Fallback to Node.js method
        try {
          fs.rmSync(this.releaseDir, { recursive: true, force: true });
          console.log('✅ Release directory cleaned using fallback method');
        } catch (error2) {
          console.log('Failed to clean with fallback:', error2.message);
          throw error;
        }
      }
    } else {
      console.log('Release directory does not exist, nothing to clean');
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
    console.log('Target directory:', targetDir);
    fs.mkdirSync(targetDir, { recursive: true });

    // Copy all required files
    console.log('Starting file copy process...');
    this.copyDirectory(this.rootDir, targetDir);

    console.log('✅ Files copied successfully');
  }

  copyDistAssets() {
    console.log('📦 Copying production assets from dist/...');

    const targetDir = path.join(this.tempDir, 'facility-locator');

    // Copy admin assets
    const adminCssSource = path.join(this.distDir, 'admin', 'css', 'facility-locator-admin.min.css');
    const adminJsSource = path.join(this.distDir, 'admin', 'js', 'facility-locator-admin.min.js');
    const adminCssTarget = path.join(targetDir, 'admin', 'css', 'facility-locator-admin.min.css');
    const adminJsTarget = path.join(targetDir, 'admin', 'js', 'facility-locator-admin.min.js');

    if (fs.existsSync(adminCssSource)) {
      fs.mkdirSync(path.dirname(adminCssTarget), { recursive: true });
      fs.copyFileSync(adminCssSource, adminCssTarget);
      console.log('📁 Copied admin CSS');
    }

    if (fs.existsSync(adminJsSource)) {
      fs.mkdirSync(path.dirname(adminJsTarget), { recursive: true });
      fs.copyFileSync(adminJsSource, adminJsTarget);
      console.log('📁 Copied admin JS');
    }

    // Copy public assets
    const publicCssSource = path.join(this.distDir, 'public', 'css', 'facility-locator-public.min.css');
    const publicJsSource = path.join(this.distDir, 'public', 'js', 'facility-locator-public.min.js');
    const publicCssTarget = path.join(targetDir, 'public', 'css', 'facility-locator-public.min.css');
    const publicJsTarget = path.join(targetDir, 'public', 'js', 'facility-locator-public.min.js');

    if (fs.existsSync(publicCssSource)) {
      fs.mkdirSync(path.dirname(publicCssTarget), { recursive: true });
      fs.copyFileSync(publicCssSource, publicCssTarget);
      console.log('📁 Copied public CSS');
    }

    if (fs.existsSync(publicJsSource)) {
      fs.mkdirSync(path.dirname(publicJsTarget), { recursive: true });
      fs.copyFileSync(publicJsSource, publicJsTarget);
      console.log('📁 Copied public JS');
    }

    console.log('✅ Production assets copied successfully');
  }

  copyDirectory(src, dest) {
    const items = fs.readdirSync(src);

    for (const item of items) {
      const srcPath = path.join(src, item);
      const destPath = path.join(dest, item);

      // Get relative path for exclusion checking
      const relativePath = path.relative(this.rootDir, srcPath);

      // Skip excluded patterns
      if (this.shouldExclude(relativePath)) {
        console.log(`⏭️  Skipping excluded: ${relativePath}`);
        continue;
      }

      const stat = fs.statSync(srcPath);

      if (stat.isDirectory()) {
        fs.mkdirSync(destPath, { recursive: true });
        this.copyDirectory(srcPath, destPath);
      } else {
        fs.copyFileSync(srcPath, destPath);
      }
    }
  }

  shouldExclude(relativePath) {
    // Normalize path separators for cross-platform compatibility
    const normalizedPath = relativePath.split(path.sep).join('/');

    // Additional safety check: Never copy release directory into itself
    if (normalizedPath.startsWith('release/') || normalizedPath === 'release') {
      return true;
    }

    return this.excludePatterns.some(pattern => {
      // Handle exact matches first
      if (pattern === normalizedPath) {
        return true;
      }

      // Convert glob patterns to regex with proper escaping
      if (pattern.includes('*')) {
        // Escape dots that are not part of glob patterns
        let regexPattern = pattern.replace(/\./g, '\\.');
        // Convert glob patterns
        regexPattern = regexPattern
          .replace(/\*\*/g, '.*')
          .replace(/\*/g, '[^/]*')
          .replace(/\?/g, '[^/]');

        const regex = new RegExp(`^${regexPattern}$`);
        return regex.test(normalizedPath);
      }

      return false;
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

  async createZipArchive() {
    console.log('📦 Creating ZIP archive...');

    try {
      const zipPath = path.join(this.releaseDir, 'facility-locator.zip');
      const facilityLocatorDir = path.join(this.tempDir, 'facility-locator');

      // Try PowerShell Compress-Archive first (creates proper ZIP files)
      try {
        execSync(`powershell -Command "Compress-Archive -Path '${facilityLocatorDir}\\*' -DestinationPath '${zipPath}' -Force"`, {
          stdio: 'pipe'
        });
        console.log('✅ ZIP archive created using PowerShell Compress-Archive');
      } catch (error) {
        console.log('PowerShell failed, trying alternative methods...');
        try {
          // Try zip command if available
          execSync(`cd "${this.tempDir}" && zip -r "../facility-locator.zip" "facility-locator"`, {
            stdio: 'pipe'
          });
          console.log('✅ ZIP archive created using zip command');
        } catch (error2) {
          // Final fallback using Node.js archiver
          await this.createZipArchiveFallback(facilityLocatorDir, zipPath);
          console.log('✅ ZIP archive created using Node.js archiver');
        }
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
    console.log('Using Node.js archiver library for ZIP creation...');

    return new Promise((resolve, reject) => {
      const archiver = require('archiver');
      const output = fs.createWriteStream(zipPath);
      const archive = archiver('zip', {
        zlib: { level: 9 } // Maximum compression
      });

      output.on('close', () => {
        console.log(`Archive finalized: ${archive.pointer()} total bytes`);
        resolve();
      });

      archive.on('error', (err) => {
        reject(err);
      });

      archive.pipe(output);
      archive.directory(sourceDir, 'facility-locator');
      archive.finalize();
    });
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