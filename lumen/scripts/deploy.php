<?php
/**
 * LICENSE
 *
 * This software is copyrighted by Mindgeek Inc. You are not allowed to
 * use this software except if the software comes attached to a written
 * approval of the author. Any usage of this software must be made with
 * previous author notification and written approval. Any usage that does not
 * comply to the written agreement is prohibited and represents criminal
 * activity.
 *
 * @copyright Mindgeek Canada Inc.
 */


// Determine the location of the root directory and include the bootstrap
$path = realpath(__DIR__ . '/..');

$config        = include($path . '/config/app.php');
$deployVersion = $config['version'];

$deploy = new Deploy();
$deploy->replaceDocumentRoot($deployVersion, $path);

/**
 * Class Deploy
 */
class Deploy
{
    private $options;

    public function __construct()
    {
        $longopts = [
            "env:"           // Required value
        ];

        $this->options = getopt(null, $longopts);
        if (!array_key_exists('env', $this->options)) {
            $this->showUsage();
            exit(1);
        }
    }

    private function showUsage()
    {
        echo "Usage: php deploy.php --env=<qa|prod|test>\n\n";
    }

    /**
     * Atomically replace the current document root with the new one.
     *
     * @param string $deployVersion Deploy version
     * @param string $appDir        Application dir
     *
     * @return string
     */
    public function replaceDocumentRoot($deployVersion, $appDir)
    {
        if (empty($deployVersion)) {
            echo " - ERROR: could not use empty version\n";
            exit(1);
        }

        echo " - detected deployment version {$deployVersion}\n";

        $copyFrom    = str_replace('/Jobs', '', $appDir);
        $syncLogPath = $copyFrom . '/storage/logs';

        $deployPath = preg_replace('!/versions/sync$!', '', $copyFrom, 1, $numReplacements);
        $copyTo     = $deployPath . '/versions/' . $deployVersion;

        $applicationLogPath = $deployPath . '/logs';
        $nextSymlink        = $deployPath . '/next';
        $currentSymlink     = $deployPath . '/current';

        if ($numReplacements !== 1) {
            echo " - ERROR: expected target deployment directory ending in '/versions/sync', got {$copyFrom}\n";
            exit(1);
        }

        //
        $this->handlePassthru(
            'chmod 755 ' . escapeshellarg($copyFrom),
            "Fix rsync permissions on versions/sync/",
            "Could not fix rsync permissions on versions/sync/"
        );

        // Point the .env symlink to the correct .env.prod | .env.qa depending on the --env option
        $envFile = $appDir . '/.env';

        $targetEnvFileName = ".env.{$this->options['env']}";
        $targetEnvFile     = "{$appDir}/{$targetEnvFileName}";

        $this->handlePassthru(
            "ln -nfs {$targetEnvFile} {$envFile}",
            "Point '.env' symlink to '{$targetEnvFileName}'",
            "Could not prepare '.env' symlink"
        );

        // Need symlink for the logs in /sync because script runs from there
        $this->handleLogFolder($applicationLogPath, $syncLogPath);

        // first, create a copy of the rsynced directory (versions/sync/) to a new
        // directory named after the deployment version (ex: versions/3.20/).
        $this->handlePassthru(
            'rsync -qrul --delete-after ' . escapeshellarg($copyFrom) . '/ ' . rtrim(escapeshellarg($copyTo), '/'),
            "Rsync versions/sync/ to versions/{$deployVersion}",
            "Could not copy versions/sync/ to current version"
        );

        // create a 'next' symlink pointing to the new version
        $this->handlePassthru(
            'ln -nfs ' . escapeshellarg($copyTo) . ' ' . escapeshellarg($nextSymlink),
            "Prepare new 'current' symlink to versions/{$deployVersion}/",
            "Could not prepare 'next' symlink"
        );

        // atomically replace the 'current' symlink with the 'next' symlink. `ln -f`
        // deletes existing which destroys atomicity, whereas `mv -T` is atomic.
        $this->handlePassthru(
            'mv -T ' . escapeshellarg($nextSymlink) . ' ' . escapeshellarg($currentSymlink),
            "Replace old 'current' symlink with new 'next' symlink",
            "Could not replace old 'current' symlink with new 'current' symlink"
        );

        return $currentSymlink;
    }

    /**
     * Method for using passthru with handling the exit
     *
     * @param string $command        The command to run through passthru
     * @param string $startMessage   Message before running the command
     * @param string $failureMessage Message when command fails
     *
     * @return void
     */
    public function handlePassthru($command, $startMessage, $failureMessage)
    {
        echo 'STARTING [' . date(DATE_RFC2822) . '] : ' . $startMessage . "\n";

        passthru($command, $exit);

        if ($exit !== 0) {
            echo "FAILED [" . date(DATE_RFC2822) . "] : " . $failureMessage . "\n";
            echo "Attempted the following command:\n" . $command . "\n";
            echo "Passthru returned: (" . $exit . ")\n";
            exit(1);
        }
    }

    /**
     * Removes log file from source and symlinks to target
     *
     * @param string $target Target folder (Outside of source code)
     * @param string $source Source folder (Inside source code)
     *
     * @return void
     */
    public function handleLogFolder($target, $source)
    {
        //Remove log folder
        $this->handlePassthru(
            'rm -fr ' . escapeshellarg($source),
            "Removing log folder {$source}",
            "Could not remove log folder"
        );

        //Create symlink pointing to application log
        $this->handlePassthru(
            'ln -nfs ' . escapeshellarg($target) . ' ' . escapeshellarg($source),
            "Create symlink for log folder",
            "Could not create symlink for log folder"
        );
    }
}