# Git Repository Cleanup Commands

This document contains the necessary Git commands to remove legacy files and apply the new .gitignore rules to your GitHub repository.

## ⚠️ **IMPORTANT: Backup First!**

Before running these commands, ensure you have:
1. **Committed all important changes**
2. **Created a backup** of your repository
3. **Tested the build system** works correctly

## 📋 **Step-by-Step Cleanup Process**

### **Step 1: Navigate to Repository Root**
```bash
cd /path/to/your/facility-locator/wp-content/plugins/facility-locator
```

### **Step 2: Remove Already-Tracked Files That Should Be Ignored**

#### **Remove Node.js Dependencies and Caches**
```bash
# Remove node_modules if tracked
git rm -r --cached node_modules/ 2>/dev/null || true

# Remove npm lock files
git rm --cached package-lock.json 2>/dev/null || true
git rm --cached yarn.lock 2>/dev/null || true

# Remove npm debug logs
git rm --cached npm-debug.log* 2>/dev/null || true
git rm --cached yarn-debug.log* 2>/dev/null || true
git rm --cached yarn-error.log* 2>/dev/null || true

# Remove build caches
git rm -r --cached .cache/ 2>/dev/null || true
git rm -r --cached .sass-cache/ 2>/dev/null || true
git rm --cached .eslintcache 2>/dev/null || true
git rm --cached .stylelintcache 2>/dev/null || true
```

#### **Remove Built Assets and Distribution Files**
```bash
# Remove dist directory (built assets)
git rm -r --cached dist/ 2>/dev/null || true

# Remove release directory
git rm -r --cached release/ 2>/dev/null || true

# Remove compressed files
git rm --cached *.zip 2>/dev/null || true
git rm --cached *.tar.gz 2>/dev/null || true
git rm --cached *.rar 2>/dev/null || true

# Remove minified directory if it exists
git rm -r --cached minified/ 2>/dev/null || true
git rm -r --cached compressed/ 2>/dev/null || true
```

#### **Remove Legacy Build Files**
```bash
# Remove the old minify script we deleted
git rm --cached build/minify.php 2>/dev/null || true

# Remove other potential legacy build files
git rm --cached gulpfile.js 2>/dev/null || true
git rm --cached gruntfile.js 2>/dev/null || true
git rm --cached webpack.mix.js 2>/dev/null || true
```

#### **Remove IDE and Editor Files**
```bash
# Remove VS Code settings
git rm -r --cached .vscode/ 2>/dev/null || true

# Remove IntelliJ/PhpStorm settings
git rm -r --cached .idea/ 2>/dev/null || true

# Remove Sublime Text files
git rm --cached *.sublime-workspace 2>/dev/null || true
git rm --cached *.sublime-project 2>/dev/null || true

# Remove other editor files
git rm -r --cached .atom/ 2>/dev/null || true
git rm --cached .brackets.json 2>/dev/null || true
git rm --cached *.code-workspace 2>/dev/null || true
```

#### **Remove Claude AI and AI Tool Files**
```bash
# Remove Claude directory and files
git rm -r --cached .claude/ 2>/dev/null || true
git rm --cached .claude-instructions-* 2>/dev/null || true
git rm --cached *.claude-session 2>/dev/null || true

# Remove other AI tool directories
git rm -r --cached .ai/ 2>/dev/null || true
git rm -r --cached .anthropic/ 2>/dev/null || true
git rm -r --cached .openai/ 2>/dev/null || true
git rm -r --cached .cursor/ 2>/dev/null || true
```

#### **Remove OS Generated Files**
```bash
# Remove macOS files
git rm --cached .DS_Store 2>/dev/null || true
git rm --cached .DS_Store? 2>/dev/null || true
git rm --cached ._* 2>/dev/null || true
git rm --cached .Spotlight-V100 2>/dev/null || true
git rm --cached .Trashes 2>/dev/null || true

# Remove Windows files
git rm --cached Thumbs.db 2>/dev/null || true
git rm --cached ehthumbs.db 2>/dev/null || true
git rm --cached Desktop.ini 2>/dev/null || true

# Remove Linux files
git rm --cached .directory 2>/dev/null || true
```

#### **Remove Logs and Temporary Files**
```bash
# Remove log files
git rm --cached *.log 2>/dev/null || true
git rm -r --cached logs/ 2>/dev/null || true
git rm --cached debug.log 2>/dev/null || true
git rm --cached error.log 2>/dev/null || true

# Remove temporary files
git rm --cached *.tmp 2>/dev/null || true
git rm --cached *.temp 2>/dev/null || true
git rm --cached *~ 2>/dev/null || true
git rm --cached *.bak 2>/dev/null || true
git rm --cached *.backup 2>/dev/null || true
git rm --cached *.swp 2>/dev/null || true
git rm --cached *.swo 2>/dev/null || true
git rm --cached *.orig 2>/dev/null || true
git rm --cached *.rej 2>/dev/null || true

# Remove temp directories
git rm -r --cached .tmp/ 2>/dev/null || true
git rm -r --cached tmp/ 2>/dev/null || true
```

#### **Remove Environment and Config Files**
```bash
# Remove environment files
git rm --cached .env 2>/dev/null || true
git rm --cached .env.local 2>/dev/null || true
git rm --cached .env.development 2>/dev/null || true
git rm --cached .env.test 2>/dev/null || true
git rm --cached .env.production 2>/dev/null || true
git rm --cached config.local.php 2>/dev/null || true

# Remove htaccess backups
git rm --cached .htaccess.backup 2>/dev/null || true
```

#### **Remove Test Coverage and Vendor Files**
```bash
# Remove test coverage
git rm -r --cached coverage/ 2>/dev/null || true
git rm -r --cached .nyc_output/ 2>/dev/null || true
git rm --cached phpunit.xml 2>/dev/null || true
git rm --cached .phpunit.result.cache 2>/dev/null || true

# Remove composer vendor directory
git rm -r --cached vendor/ 2>/dev/null || true
git rm --cached composer.lock 2>/dev/null || true
```

#### **Remove Development-Specific Directories**
```bash
# Remove development directories
git rm -r --cached src/temp/ 2>/dev/null || true
git rm -r --cached dev-tools/ 2>/dev/null || true
git rm -r --cached .dev/ 2>/dev/null || true
git rm -r --cached .development/ 2>/dev/null || true
```

### **Step 3: Update .gitignore and Stage Changes**
```bash
# Stage the updated .gitignore file
git add .gitignore

# Stage any new files that should be tracked
git add src/
git add package.json
git add webpack.config.js
git add .babelrc
git add .eslintrc.js
git add build/scripts/
git add build/postcss.config.js
git add README.md
git add CHANGELOG.md
```

### **Step 4: Commit the Cleanup**
```bash
# Commit all the removals and new gitignore
git commit -m "🧹 Clean up repository: remove built assets, legacy files, and IDE configs

- Remove dist/, release/, node_modules/ from tracking
- Remove IDE configuration files (.vscode/, .idea/, etc.)
- Remove Claude AI and other AI tool directories
- Remove OS generated files (DS_Store, Thumbs.db, etc.)
- Remove build caches and temporary files
- Remove legacy build system files
- Update .gitignore with comprehensive exclusions
- Add modern build configuration files
- Add source files and documentation

This cleanup prepares the repository for professional development
and removes unnecessary files that shouldn't be tracked in version control."
```

### **Step 5: Verify the Cleanup**
```bash
# Check what files are now being tracked
git ls-files

# Check what files are ignored
git status --ignored

# Verify the build still works
npm run build
```

### **Step 6: Push to GitHub**
```bash
# Push the cleaned up repository
git push origin main
```

## 🔍 **Verification Commands**

After cleanup, run these commands to verify everything is working:

```bash
# 1. Check repository status
git status

# 2. Verify no unwanted files are tracked
git ls-files | grep -E '\.(log|tmp|cache)$|node_modules|\.vscode|\.idea|dist/' || echo "✅ No unwanted files found"

# 3. Test the build system
npm install
npm run build

# 4. Verify built files are not tracked
git status | grep "dist/" && echo "❌ Built files are being tracked!" || echo "✅ Built files properly ignored"

# 5. Check .gitignore is working
echo "test" > test.log
git status | grep "test.log" && echo "❌ .gitignore not working for .log files" || echo "✅ .gitignore working correctly"
rm test.log
```

## 📊 **Expected Results**

After running these commands, your repository should:

### ✅ **Files That Should Be Tracked:**
- Source files (`src/`)
- PHP files (`*.php`)
- Configuration files (`package.json`, `webpack.config.js`, etc.)
- Documentation (`README.md`, `CHANGELOG.md`)
- Templates and includes
- WordPress plugin files

### ❌ **Files That Should NOT Be Tracked:**
- Built assets (`dist/`)
- Node modules (`node_modules/`)
- IDE configuration files
- OS generated files
- Log files and temporary files
- Claude AI directories
- Environment configuration files

## 🚨 **Troubleshooting**

### **If files won't be removed:**
```bash
# Force remove if files are stubborn
git rm -rf --cached problematic-directory/

# Or for individual files
git rm -f --cached problematic-file.ext
```

### **If .gitignore isn't working:**
```bash
# Clear Git cache completely and re-add everything
git rm -r --cached .
git add .
git commit -m "Apply .gitignore to all files"
```

### **If you accidentally remove important files:**
```bash
# Restore from the last commit
git checkout HEAD -- filename

# Or restore everything
git reset --hard HEAD
```

## ✅ **Final Checklist**

- [ ] Repository is backed up
- [ ] All unwanted files removed from tracking
- [ ] .gitignore properly excludes built assets
- [ ] Build system works: `npm run build`
- [ ] Only source files and configuration are tracked
- [ ] Changes committed and pushed to GitHub

Your repository is now clean and ready for professional development! 🎉