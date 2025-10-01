const fs = require('fs');
const path = require('path');
const archiver = require('archiver');
const { execSync } = require('child_process');

class ZipCreator {
  constructor() {
    this.rootDir = path.resolve(__dirname, '../..');
    this.releaseDir = path.join(this.rootDir, 'release');
    this.tempDir = path.join(this.releaseDir, 'temp');
    this.pluginName = 'facility-locator';
  }

  async createZip() {
    console.log('🗜️  Creating WordPress.org ready ZIP package...');

    try {
      this.validateEnvironment();
      await this.createZipArchive();
      this.cleanup();

      console.log('✅ ZIP package created successfully!');
      console.log(`📦 Location: ${path.join(this.releaseDir, `${this.pluginName}.zip`)}`);

    } catch (error) {
      console.error('❌ ZIP creation failed:', error.message);
      process.exit(1);
    }
  }

  validateEnvironment() {
    // Check if temp directory exists (should be created by release script)
    if (!fs.existsSync(this.tempDir)) {
      throw new Error('Temp directory not found. Run "npm run release:prepare" first.');
    }

    const pluginDir = path.join(this.tempDir, this.pluginName);
    if (!fs.existsSync(pluginDir)) {
      throw new Error('Plugin directory not found in temp folder.');
    }
  }

  async createZipArchive() {
    return new Promise((resolve, reject) => {
      const zipPath = path.join(this.releaseDir, `${this.pluginName}.zip`);
      const output = fs.createWriteStream(zipPath);
      const archive = archiver('zip', {
        zlib: { level: 9 } // Maximum compression
      });

      output.on('close', () => {
        const sizeInMB = (archive.pointer() / 1024 / 1024).toFixed(2);
        console.log(`📊 Archive size: ${sizeInMB} MB`);
        resolve();
      });

      archive.on('error', (err) => {
        reject(err);
      });

      archive.pipe(output);

      // Add the plugin directory to the zip
      const pluginDir = path.join(this.tempDir, this.pluginName);
      archive.directory(pluginDir, this.pluginName);

      archive.finalize();
    });
  }

  cleanup() {
    console.log('🧹 Cleaning up temporary files...');

    if (fs.existsSync(this.tempDir)) {
      fs.rmSync(this.tempDir, { recursive: true, force: true });
    }
  }
}

// Run if called directly
if (require.main === module) {
  const creator = new ZipCreator();
  creator.createZip().catch(error => {
    console.error('ZIP creation failed:', error);
    process.exit(1);
  });
}

module.exports = ZipCreator;