<?php

namespace mc {

    class filesystem {

        public const US = "/";
        public const WS = "\\";

        public static function normalize(string $path, string $separator = self::US): string {
            if ($separator === self::US) {
                return self::to_unix($path);
            } elseif ($separator === self::WS) {
                return self::to_windows($path);
            }
            return $path;
        }

        public static function to_unix(string $path): string {
            return str_replace(self::WS, self::US, $path);
        }

        public static function to_windows(string $path): string {
            return str_replace(self::US, self::WS, $path);
        }

        public static function root(string $path, string $separator = self::US): string {
            $path = self::normalize($path, $separator);
            $chunks = explode($separator, $path);
            $last = array_pop($chunks);
            return implode($separator, $chunks);
        }

        public static function children(string $path, string $separator = self::US): string {
            $path = self::normalize($path, $separator);
            $chunks = explode($separator, $path);
            $last = array_pop($chunks);
            return $last;
        }

        public static function implode(string $left, string $right, string $separator = self::US): string {
            $left = self::normalize($left, $separator);
            $right = self::normalize($right, $separator);
            $chunks = explode($separator, $left);
            foreach (explode($separator, $right) as $chunk) {
                $chunks[] = $chunk;
            }
            return implode($separator, $chunks);
        }

        public static function copy(string $from, string $to, string $separator = self::US) {
            $from = self::normalize($from, $separator);
            $to = self::normalize($to, $separator);

            foreach ($iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
            ) as $item) {
                if ($item->isDir()) {
                    if (!file_exists($to . $separator . $iterator->getSubPathname())) {
                        mkdir($to . $separator . $iterator->getSubPathname());
                    }
                } else {
                    copy($item, $to . $separator . $iterator->getSubPathname());
                }
            }
        }

        public static function unlink(string $path) {
            if (\is_dir($path)) {
                $files = \array_diff(scandir($path), ['.', '..']);
                foreach ($files as $file) {
                    self::unlink($path . DIRECTORY_SEPARATOR . $file);
                }
                \rmdir($path);
            } else {
                \unlink($path);
            }
        }
    }

    /**
     * repository downloader
     */
    class repository {

        public const ORIGIN = "origin";
        public const REPOSITORY = "repository";
        public const BRANCH = "branch";
        public const USER = "user";
        public const TOKEN = "token";
        public const SOURCE = "source";
        public const DESTINATION = "destination";
        private const TMPDIR = "./__tmp__";

        private string $origin = "https://github.com/";
        private string $repository;
        private string $branch = "main";
        private string $user;
        private string $token = "";
        private string $source;
        private string $destination = "./modules";

        /**
         * create repository downloader from $config array
         * @param array $config
         */
        public function __construct(array $config) {
            foreach ($config as $key => $value) {
                $this->$key = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
            }

            $this->source = $this->repository() . "-" . $this->branch();
            if (!empty($config["source"])) {
                $this->source = \mc\filesystem::implode($this->source(), $config["source"]);
            }
        }

        /**
         * returns repository host / origin
         * @return string
         */
        public function origin() {
            return $this->origin;
        }

        /**
         * returns repository name
         * @return string
         */
        public function repository() {
            return $this->repository;
        }

        /**
         * returns repository branch
         * @return string
         */
        public function branch() {
            return $this->branch;
        }

        /**
         * returns user / organisation name, repository owner
         * @return string
         */
        public function user() {
            return $this->user;
        }

        /**
         * returns source for copy
         * @return string
         */
        public function source() {
            return $this->source;
        }

        /**
         * returns path for repository storing.
         * @return string
         */
        public function destination() {
            return $this->destination;
        }

        /**
         * returns URL to the archived repository branch
         * @return string
         */
        public function url() {
            return "{$this->origin}{$this->user}/{$this->repository}/archive/refs/heads/{$this->branch}.zip";
        }

        /**
         * download repository branch to the $destination folder
         * @param string $destination
         */
        public function download(string $destination = "") {
            if (!file_exists(self::TMPDIR)) {
                mkdir(self::TMPDIR);
            }

            if (empty($destination)) {
                $destination = $this->destination;
            }

            if (!file_exists($destination)) {
                echo "[debug] dest folder = {$destination}\n";
                mkdir($destination, 0777, true);
            }

            $ch = curl_init($this->url());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            if (!empty($this->token)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Authorization: token " . $this->token
                ));
            }

            $zipfile = curl_exec($ch);
            curl_close($ch);

            $tmpname = \mc\filesystem::implode(self::TMPDIR, "{$this->user()}_{$this->repository()}.zip");

            file_put_contents($tmpname, $zipfile);
            return $tmpname;
        }

        /**
         * unpack a repo from archive
         * @param type $archive
         */
        public function unpack($archive) {
            $zip = new \ZipArchive();
            $zip->open($archive);
            $files = [];
            $count = $zip->count();

            for ($i = 0; $i < $count; ++$i) {
                $fileName = $zip->getNameIndex($i);
                // skip dangerous files
                if ($fileName[0] === '/' || $fileName[0] === '.' || strpos($fileName, '../')) {
                    continue;
                }
                $files[] = $fileName;
            }

            foreach ($files as $file) {
                $content = $zip->getFromName($file);
                $path = $this->destination . str_replace("{$this->repository}-{$this->branch}", "", $file);
                if (substr($path, -1, 1) == '/') {
                    if (!file_exists($path)) {
                        mkdir($path);
                    }
                    continue;
                }
                file_put_contents($path, $content);
            }
            $zip->close();
        }

        /**
         * remove content of $this->destination folder
         */
        public function drop() {
            $files = glob($this->destination . "*");

            foreach ($files as $file) {
                \mc\filesystem::unlink($file);
            }
        }
    }

}

namespace {
    $longopts = [
        "help",
        "info",
        "install",
        "reinstall",
        "drop",
        "entrypoint::",
        "config::"
    ];

    $config_file = "modules.json";
    $entrypoint = "./modules/entrypoint.php";
    $debug = false;

    /**
     * print usage
     */
    function usage() {
        echo "Usage: " . __FILE__ . " [options]" . PHP_EOL;
        echo "Options:" . PHP_EOL;
        echo "  --help                 Show this help" . PHP_EOL;
        echo "  --info                 Print module configuration" . PHP_EOL;
        echo "  --install              Install libraries" . PHP_EOL;
        echo "  --reinstall            Reinstall libraries" . PHP_EOL;
        echo "  --drop                 Drop all libraries" . PHP_EOL;
        echo "  --entrypoint=<path>    Path to the entrypoint file" . PHP_EOL;
        echo "  --config=<path>        Path to the config file" . PHP_EOL;
    }

    /**
     * print module configuration
     * @param string config file
     */
    function info(string $config_file) {
        if (!file_exists($config_file)) {
            echo "Config file '{$config_file}' not found" . PHP_EOL;
            return;
        }
        $config = json_decode(file_get_contents($config_file), true);
        echo "module configuration:" . PHP_EOL;
        foreach ($config as $module_config) {
            $repo = new \mc\repository($module_config);
            echo "\t" . $repo->user() . "/" . $repo->repository()
            . " => " . $repo->destination() . " : " . $repo->url() . PHP_EOL;
        }
    }

    /**
     * install modules defined in the config file
     * @param string config file
     */
    function install(string $config_file) {
        if (!file_exists($config_file)) {
            echo "Config file '{$config_file}' not found" . PHP_EOL;
            return;
        }
        $config = json_decode(file_get_contents($config_file), true);

        foreach ($config as $module_config) {
            echo "Installing {$module_config['user']}/{$module_config['repository']} ... ";
            $path = empty($module_config["destination"]) ? "./modules" : $module_config["destination"];
            // check if destination folder exists, create it
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $path .= DIRECTORY_SEPARATOR . $module_config["repository"];
            // if repository folder exists, warn
            if (file_exists($path)) {
                echo PHP_EOL;
                echo "[warn] module {$module_config['repository']} exists.";
                echo " Did you want to reinstall it? SKIP MODULE" . PHP_EOL;
                continue;
            }

            // download
            $repo = new \mc\repository($module_config);
            $repoZip = $repo->download();
            $repo->unpack($repoZip);

            echo "[OK]" . PHP_EOL;
        }
    }

    /**
     * drop modules defined in config file
     * @param string config file
     */
    function drop(string $config_file) {
        if (!file_exists($config_file)) {
            echo "Config file '{$config_file}' not found" . PHP_EOL;
            return;
        }

        $config = json_decode(file_get_contents($config_file), true);

        foreach ($config as $module_config) {
            echo "Dropping {$module_config['user']}/{$module_config['repository']} ... ";
            $manager = new \mc\repository($module_config);
            $manager->drop();
            echo "[OK]" . PHP_EOL;
        }
    }

    /**
     * reinstall modules defined in the config file
     * @param string config file
     */
    function reinstall(string $config_file) {
        echo "Reinstalling modules ... " . PHP_EOL;
        drop($config_file);
        install($config_file);
        echo "Done." . PHP_EOL;
    }

    /**
     * create entrypoint.php file
     * @param string config file
     * @param string entry point, default value 'entrypoint.php'
     */
    function entrypoint(string $config_file, string $entrypoint = "entrypoint.php") {
        $result = "<?php" . PHP_EOL . PHP_EOL;

        if (!file_exists($config_file)) {
            echo "Config file not found" . PHP_EOL;
            return;
        }

        $config = json_decode(file_get_contents($config_file), true);

        foreach ($config as $module_config) {
            // check if entry point is defined in the module config
            if (empty($module_config["entrypoint"])) {
                continue;
            }

            // check if file exists
            $path = empty($module_config["destination"]) ? "./modules" : $module_config["destination"];
            $path .= "/" . $module_config["entrypoint"];
            if (!file_exists($path)) {
                echo "[warn] entry point {$path} for {$module_config['repository']} is missing" . PHP_EOL;
                continue;
            }

            // add include_once to result
            $result .= "include_once '{$path}';" . PHP_EOL;
        }

        $root = \mc\filesystem::root($entrypoint);
        if (!file_exists($root)) {
            mkdir($root);
        }

        file_put_contents($entrypoint, $result);
    }

    $opts = getopt("", $longopts);

    if (isset($opts["help"]) ||
        !(isset($opts["install"]) || isset($opts["info"]) ||
        isset($opts["reinstall"]) || isset($opts["drop"]) )) {
        usage();
        exit(0);
    }

    if (isset($opts["config"])) {
        $config_file = $opts["config"];
    }

    if (isset($opts["entrypoint"])) {
        $entrypoint = $opts["entrypoint"];
    }

    if (isset($opts["info"])) {
        info($config_file);
        exit(0);
    }

    if (isset($opts["install"])) {
        install($config_file);
        entrypoint($config_file, $entrypoint);
        exit(0);
    }

    if (isset($opts["reinstall"])) {
        reinstall($config_file);
        entrypoint($config_file, $entrypoint);
        exit(0);
    }

    if (isset($opts["drop"])) {
        drop($config_file);
        if (file_exists($entrypoint)) {
            unlink($entrypoint);
        }
        exit(0);
    }
}
