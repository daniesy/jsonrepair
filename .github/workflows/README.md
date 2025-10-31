# GitHub Actions Workflows

This repository uses GitHub Actions for automated testing, code quality checks, and releases.

## Workflows

### 🧪 Tests (`tests.yml`)

**Triggers**: On push and pull requests to `main` and `develop` branches

**What it does**:
- Runs on PHP 8.4 with Ubuntu
- Validates `composer.json` structure
- Installs dependencies
- Runs all test suites:
  - Full test suite (81 tests)
  - Unit tests only (66 tests)
  - Streaming tests only (15 tests)
- Generates code coverage report

**Status Badge**:
```markdown
[![Tests](https://github.com/USERNAME/jsonrepair-php/workflows/Tests/badge.svg)](https://github.com/USERNAME/jsonrepair-php/actions?query=workflow%3ATests)
```

---

### 📦 Release (`release.yml`)

**Triggers**: When a version tag is pushed (e.g., `v1.0.0`, `v1.0.1`)

**What it does**:
1. **Validates** the release:
   - Validates `composer.json`
   - Runs all tests
   - Extracts version from tag
2. **Creates GitHub Release**:
   - Extracts changelog for the version from `CHANGELOG.md`
   - Creates a release with notes
3. **Notifies Packagist** (optional):
   - Triggers Packagist update if credentials are configured
   - Falls back to webhook auto-update

**How to create a release**:
```bash
# Update CHANGELOG.md with changes
# Commit changes
git add CHANGELOG.md
git commit -m "Prepare release v1.0.1"
git push

# Create and push tag
git tag -a v1.0.1 -m "Release version 1.0.1"
git push origin v1.0.1

# GitHub Actions will automatically:
# - Run tests
# - Create GitHub release
# - Notify Packagist
```

**Optional Packagist Credentials**:

If you want manual Packagist updates (not needed if webhook is set up):

1. Go to https://packagist.org/profile/
2. Click "Show API Token"
3. In your GitHub repo: Settings → Secrets → Actions
4. Add secrets:
   - `PACKAGIST_USERNAME`: Your Packagist username
   - `PACKAGIST_TOKEN`: Your API token

---

### ✅ Code Quality (`code-quality.yml`)

**Triggers**: On push and pull requests to `main` and `develop` branches

**What it does**:
- **PHP Syntax Check**: Validates all PHP files for syntax errors
- **Composer Validation**: Checks `composer.json` format and structure
- **Security Check**: Runs `composer audit` for known vulnerabilities
- **Examples Test**: Runs example files to ensure they work

**Status Badge**:
```markdown
[![Code Quality](https://github.com/USERNAME/jsonrepair-php/workflows/Code%20Quality/badge.svg)](https://github.com/USERNAME/jsonrepair-php/actions?query=workflow%3A%22Code+Quality%22)
```

---

## Adding Badges to README

Add these badges to your `README.md`:

```markdown
[![Tests](https://github.com/USERNAME/jsonrepair-php/workflows/Tests/badge.svg)](https://github.com/USERNAME/jsonrepair-php/actions?query=workflow%3ATests)
[![Code Quality](https://github.com/USERNAME/jsonrepair-php/workflows/Code%20Quality/badge.svg)](https://github.com/USERNAME/jsonrepair-php/actions?query=workflow%3A%22Code+Quality%22)
[![Latest Version](https://img.shields.io/packagist/v/jsonrepair/jsonrepair.svg?style=flat-square)](https://packagist.org/packages/jsonrepair/jsonrepair)
[![Total Downloads](https://img.shields.io/packagist/dt/jsonrepair/jsonrepair.svg?style=flat-square)](https://packagist.org/packages/jsonrepair/jsonrepair)
[![PHP Version](https://img.shields.io/packagist/php-v/jsonrepair/jsonrepair.svg?style=flat-square)](https://packagist.org/packages/jsonrepair/jsonrepair)
[![License](https://img.shields.io/packagist/l/jsonrepair/jsonrepair.svg?style=flat-square)](https://packagist.org/packages/jsonrepair/jsonrepair)
```

Replace `USERNAME` with your GitHub username.

---

## Workflow Features

### ✨ Automatic Features

- **Caching**: Composer packages are cached for faster builds
- **Matrix Testing**: Easy to add more PHP versions if needed
- **Parallel Jobs**: Tests and quality checks run in parallel
- **Smart Notifications**: Only fails alert you
- **Auto Releases**: Tag → Test → Release → Packagist

### 🔧 Customization

#### Test on Multiple PHP Versions

Edit `tests.yml`:
```yaml
matrix:
  php: ['8.4', '8.5']  # Add more versions
```

#### Add More Operating Systems

Edit `tests.yml`:
```yaml
matrix:
  os: [ubuntu-latest, windows-latest, macos-latest]
```

#### Customize Release Notes

Edit `release.yml` to change how changelog is extracted.

---

## Troubleshooting

### Workflow Fails

1. **Check the logs**: Click on the failed workflow in Actions tab
2. **Common issues**:
   - PHP version not available: Update `setup-php` action
   - Tests fail: Run tests locally first
   - Composer errors: Check `composer.json` validity

### Packagist Not Updating

If Packagist doesn't auto-update:

1. **Check webhook**: GitHub repo → Settings → Webhooks
2. **Verify URL**: `https://packagist.org/api/github?username=YOUR_USERNAME`
3. **Recent Deliveries**: Check if webhook is firing
4. **Manual update**: Add Packagist credentials to GitHub secrets

### Release Workflow Not Triggering

- Ensure tag format: `v1.0.0` (must start with `v`)
- Push tags: `git push origin v1.0.0`
- Check Actions tab for workflow runs

---

## Local Development

To run checks locally before pushing:

```bash
# Run all tests
composer test

# Validate composer.json
composer validate --strict

# Check syntax
find src tests -name "*.php" -exec php -l {} \;

# Run examples
php examples/streaming_example.php

# Security audit
composer audit
```

---

## Contributing

When contributing:
1. Fork the repository
2. Create a feature branch
3. Make changes and add tests
4. Push and create a pull request
5. GitHub Actions will automatically run tests
6. Wait for green checks before merging
