<?php

$root = getcwd();

// Define copies: [source, destination]
$copies = [
  [
    $root . '/vendor/hubleto/assets',
    $root . '/public/assets',
  ],
];

function copyDirectory(string $source, string $destination): void
{
    if (!is_dir($source)) {
        throw new RuntimeException("Source directory does not exist: {$source}");
    }

    if (!is_dir($destination) && !mkdir($destination, 0777, true)) {
        throw new RuntimeException("Cannot create directory: {$destination}");
    }

    $iterator = new FilesystemIterator(
        $source,
        FilesystemIterator::SKIP_DOTS
    );

    foreach ($iterator as $item) {
        $sourcePath = $item->getPathname();
        $destinationPath = $destination . DIRECTORY_SEPARATOR . $item->getFilename();

        if ($item->isLink()) {
            // Preserve symbolic links rather than following them
            $target = readlink($sourcePath);

            if ($target === false || !symlink($target, $destinationPath)) {
                throw new RuntimeException("Cannot copy symlink: {$sourcePath}");
            }
        } elseif ($item->isDir()) {
            copyDirectory($sourcePath, $destinationPath);
        } elseif ($item->isFile()) {
            if (!copy($sourcePath, $destinationPath)) {
                throw new RuntimeException("Cannot copy file: {$sourcePath}");
            }
        }
    }
}

function removeDirectory(string $directory): void
{
    if (!is_dir($directory) || is_link($directory)) {
        if (file_exists($directory) || is_link($directory)) {
            if (!unlink($directory)) {
                throw new RuntimeException("Cannot remove: {$directory}");
            }
        }
        return;
    }

    foreach (new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS) as $item) {
        $path = $item->getPathname();

        if ($item->isLink() || $item->isFile()) {
            if (!unlink($path)) {
                throw new RuntimeException("Cannot remove: {$path}");
            }
        } else {
            removeDirectory($path);
        }
    }

    if (!rmdir($directory)) {
        throw new RuntimeException("Cannot remove directory: {$directory}");
    }
}

try {
    foreach ($copies as [$source, $destination]) {
        echo "Copying: {$source} -> {$destination}\n";

        // Remove previous destination to avoid stale files
        if (file_exists($destination) || is_link($destination)) {
            removeDirectory($destination);
        }

        copyDirectory($source, $destination);

        echo "Done: {$destination}\n";
    }

    echo "All directories copied successfully.\n";

} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: {$e->getMessage()}\n");
    exit(1);
}

echo "All symlinks created successfully.\n";