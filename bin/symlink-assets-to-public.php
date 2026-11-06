<?php

$root = getcwd();

// Define symlinks: [target, link]
$links = [
  [
    $root . '/vendor/hubleto/assets/public',
    $root . '/public/assets',
  ],
];

foreach ($links as [$target, $link]) {
  try {
    // Ensure target exists
    if (!file_exists($target)) {
      throw new RuntimeException("Target does not exist: {$target}");
    }

    // Remove existing file or symlink
    if (is_link($link) || file_exists($link)) {
      if (is_dir($link) && !is_link($link)) {
        throw new RuntimeException(
          "Link destination is an existing directory: {$link}"
        );
      }

      if (!unlink($link)) {
        throw new RuntimeException(
          "Cannot remove existing link: {$link}"
        );
      }
    }

    // Ensure parent directory exists
    $parent = dirname($link);

    if (!is_dir($parent) && !mkdir($parent, 0777, true)) {
      throw new RuntimeException(
        "Cannot create parent directory: {$parent}"
      );
    }

    // Create symlink
    if (PHP_OS_FAMILY === 'Windows') {
      $command = 'cmd /c mklink /J '
        . escapeshellarg($link)
        . ' '
        . escapeshellarg($target);

      exec($command, $output, $returnCode);

      if ($returnCode !== 0) {
        throw new RuntimeException(
          "Could not create symlinks. Run this command manually: `{$command}`\n"
          . implode("\n", $output));
      }
    } else {
      if (!symlink($target, $link)) {
        throw new RuntimeException("Cannot create symlink: {$link}");
      }
    }
    // if (!symlink($target, $link)) {
    //   throw new RuntimeException(
    //     "Cannot create symlink: {$link} -> {$target}"
    //   );
    // }

    echo "Created: {$link} -> {$target}\n";

  } catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
  }
}

echo "All symlinks created successfully.\n";