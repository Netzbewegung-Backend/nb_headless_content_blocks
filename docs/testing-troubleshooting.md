# Testing troubleshooting

Symptom → cause → fix for problems in this extension's **own test setup**
(composer version switches, `runTests.sh`, GitHub Actions/`act`). Problems
when *using* the extension are covered in
[Troubleshooting](troubleshooting.md).

## Unit tests fail with `GeneralUtility::makeInstance` of container-only services

**Symptom:** unit tests (`Build/Scripts/runTests.sh -s unit`) fail resolving
services like `TcaSchemaFactory`; the same code works in functional tests.

**Cause:** by design — unit tests run **without any DI container**. Code
must tolerate missing container-only services (see `AGENTS.md` → Testing
Gotchas).

**Fix:** make the service optional (nullable constructor argument +
runtime fallback) as the core classes of this extension do.

## Functional tests fail with `Can not remove folder` after running GitHub Actions locally

**Symptom:** `Build/Scripts/runTests.sh -s functional` aborts with
`TYPO3\TestingFramework\Core\Exception: Can not remove folder`.

**Cause:** `act` runs its job containers as root, leaving root-owned folders
below `.Build/public/typo3temp/var/tests/functional-*` on the shared Docker
daemon.

**Fix:** detect them with

```bash
find .Build/public/typo3temp/var/tests -maxdepth 1 -user root
```

and have a user with root delete those folders (`sudo rm -rf <folder>`).
More `act` gotchas (matrix parallelism, composer cache eviction): see
`AGENTS.md` → Testing Gotchas and `.github/TEST-GITHUB-WORKFLOWS.md`.

## Functional tests fail with `Package "headless" depends on package "install" which does not exist.`

**Symptom:** a functional test that loads EXT:headless aborts during
test instance creation with the message above (thrown by the testing
framework's `PackageCollection`).

**Cause:** headless ≥ 5.0.0-rc2 ships no `ext_emconf.php`, so the testing
framework resolves the composer dependencies to sort the classic-mode
test instance packages. headless requires `typo3/cms-install`, but
`install` is not among the default core extensions of a functional test
instance (`core`, `backend`, `frontend`, `extbase`, `fluid`).

**Fix:** add `install` to `$coreExtensionsToLoad` of the test case (see
`ContentBlocksJsonResponseTest`).
