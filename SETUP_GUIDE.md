# Setup Guide for Publishing jsonrepair-php

This guide will help you publish your PHP port to GitHub and Packagist.

## Step 1: Create GitHub Repository

1. Go to https://github.com/new
2. **Repository name**: `jsonrepair-php`
3. **Description**: "A modern PHP 8.4+ port of jsonrepair with streaming support for repairing invalid JSON documents"
4. **Public repository** (required for Packagist)
5. **Don't initialize** with README (we already have files)
6. Click "Create repository"

## Step 2: Update Your GitHub Username (if needed)

If your GitHub username is different from `danflorian`, update these files:

### composer.json
```json
"homepage": "https://github.com/YOUR-USERNAME/jsonrepair-php",
"authors": [
    ...
    {
        "name": "Dan Florian",
        "homepage": "https://github.com/YOUR-USERNAME",
        "role": "PHP Port Author"
    }
],
"support": {
    "issues": "https://github.com/YOUR-USERNAME/jsonrepair-php/issues",
    "source": "https://github.com/YOUR-USERNAME/jsonrepair-php"
}
```

### README.md
```bash
git clone https://github.com/YOUR-USERNAME/jsonrepair-php.git
```

## Step 3: Initialize Git and Push

```bash
# Navigate to the php directory
cd /Users/daniesy/Downloads/jsonrepair-main/php

# Initialize git
git init

# Add all files
git add .

# Create initial commit
git commit -m "Initial release v1.0.0

- Complete PHP 8.4+ port of jsonrepair
- Streaming support with generators
- 81 tests passing (100% coverage)
- PSR-4 autoloading
- Modern PHP features (enums, readonly, match)
- Complete documentation and examples"

# Add your GitHub repository as remote
git remote add origin https://github.com/YOUR-USERNAME/jsonrepair-php.git

# Create and push main branch
git branch -M main
git push -u origin main

# Create version tag
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

## Step 4: Publish to Packagist

1. Go to https://packagist.org/
2. **Sign in** or **Register** with your GitHub account
3. Click **"Submit"** at the top
4. Enter your repository URL: `https://github.com/YOUR-USERNAME/jsonrepair-php`
5. Click **"Check"** and then **"Submit"**

### Enable Auto-Update (Recommended)

Packagist can automatically update when you push to GitHub:

1. In your Packagist package page, find the GitHub service hook
2. Or go to your GitHub repo → Settings → Webhooks → Add webhook
3. **Payload URL**: `https://packagist.org/api/github?username=YOUR-PACKAGIST-USERNAME`
4. **Content type**: `application/json`
5. **Events**: "Just the push event"
6. Click "Add webhook"

## Step 5: Update Package Badge (Optional)

Once published, update README.md badges with actual URLs:

```markdown
[![Latest Version](https://img.shields.io/packagist/v/jsonrepair/jsonrepair.svg?style=flat-square)](https://packagist.org/packages/jsonrepair/jsonrepair)
[![Total Downloads](https://img.shields.io/packagist/dt/jsonrepair/jsonrepair.svg?style=flat-square)](https://packagist.org/packages/jsonrepair/jsonrepair)
[![PHP Version](https://img.shields.io/packagist/php-v/jsonrepair/jsonrepair.svg?style=flat-square)](https://packagist.org/packages/jsonrepair/jsonrepair)
[![License](https://img.shields.io/packagist/l/jsonrepair/jsonrepair.svg?style=flat-square)](https://packagist.org/packages/jsonrepair/jsonrepair)
```

## Step 6: Add Repository Topics on GitHub

Add these topics to your GitHub repository for better discoverability:

```
php
php8
json
json-parser
json-repair
composer
packagist
streaming
generator
mongodb
ndjson
```

To add topics:
1. Go to your repo on GitHub
2. Click the gear icon next to "About"
3. Add topics in the "Topics" field

## Step 7: Create a Nice README on GitHub

GitHub will display your README.md automatically. Consider adding:

1. **Badges** at the top (see Step 5)
2. **Quick example** in the beginning
3. **Link to Packagist** once published
4. **Link to original project** (giving credit to Jos de Jong)

## Step 8: Announce Your Package

Consider sharing your package:

1. **Reddit**: r/PHP
2. **Twitter/X**: Use hashtags #PHP #Composer #OpenSource
3. **Dev.to**: Write a blog post about the port
4. **PHP Weekly**: Submit to https://www.phpweekly.com/
5. **Original Author**: Consider opening an issue or PR on the original repo to let Jos de Jong know about the PHP port

## Future Updates

### Creating New Releases

```bash
# Make your changes and commit
git add .
git commit -m "Fix: Description of fix"

# Update CHANGELOG.md with changes

# Create new tag (following semver)
git tag -a v1.0.1 -m "Release version 1.0.1"
git push origin main
git push origin v1.0.1

# Packagist will auto-update if webhook is configured
```

### Semantic Versioning

- **Patch** (1.0.x): Bug fixes, backward compatible
- **Minor** (1.x.0): New features, backward compatible
- **Major** (x.0.0): Breaking changes

## Verifying Installation

After publishing, verify users can install:

```bash
# In a new directory
composer require jsonrepair/jsonrepair

# Test it works
php -r "require 'vendor/autoload.php'; use function JsonRepair\jsonrepair; echo jsonrepair('{name: \"John\"}');"
```

## Getting Help

- **Composer Issues**: https://getcomposer.org/doc/
- **Packagist Help**: https://packagist.org/about
- **Git Help**: https://docs.github.com/

## Checklist

- [ ] GitHub repository created
- [ ] Updated GitHub username in files (if needed)
- [ ] Git initialized and pushed to GitHub
- [ ] Version tag created (v1.0.0)
- [ ] Submitted to Packagist
- [ ] Auto-update webhook configured
- [ ] Repository topics added
- [ ] README badges updated (after publishing)
- [ ] Installation verified
- [ ] (Optional) Announced to PHP community

## Notes

- The package name on Packagist will be: `jsonrepair/jsonrepair`
- Install with: `composer require jsonrepair/jsonrepair`
- Packagist syncs with GitHub tags automatically (if webhook configured)
- Minimum PHP version required: 8.4

Good luck with your package! 🚀
