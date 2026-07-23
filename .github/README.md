# GitHub Actions - Local Testing with ACT

[Nektos ACT](https://github.com/nektos/act) allows running GitHub Actions locally.

## Installation

```bash
curl --proto '=https' --tlsv1.2 -sSf https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash
```

## Available Jobs

| Job | Description |
|-----|-------------|
| `early_cgl` | CGL (PHP CS Fixer) |
| `unit_tests` | Unit Tests |
| `functional_tests` | Functional Tests |
| `PHPStan` | Static Analysis |

## Run Jobs

```bash
cd /var/www/vhosts/nb_headless_content_blocks

# List available jobs
act -l

# Run CGL check
act -j early_cgl

# Run PHPStan
act -j PHPStan

# Run functional tests - TYPO3 13.4
act -j functional_tests --matrix typo3:^13.4 --matrix php:8.2 --matrix content-blocks:^1.2 --matrix headless:^4.5 --matrix container:^3.1

# Run functional tests - TYPO3 14.3
act -j functional_tests --matrix typo3:^14.3 --matrix php:8.4 --matrix content-blocks:^2.0 --matrix headless:^5.0@RC --matrix container:^4.0
```
