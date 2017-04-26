<?php
class Downloader
{
    private $urls = [];
    private $audio_only = false;
    private $errors = [];
    private $download_path = "";
    private $dl_format = "-f best";
    private $config = [];
    
    public function __construct($post, $audio_only, $dl_format, $audio_format)
    {
        $this->download_path = (new FileHandler())->get_downloads_folder();
        $this->config = $GLOBALS['config'];
        $this->audio_only = $audio_only;
        $this->audio_format = $audio_format;
        $this->dl_format = $dl_format;
        $this->urls = explode(",", $post);
      
        if(!$this->check_requirements($audio_only)) {
            return;
        }
      
        foreach ($this->urls as $url) {
            if(!$this->is_valid_url($url)) {
                $this->errors[] = "\"".$url."\" is not a valid url !";
            }
        }
      
        if(isset($this->errors) && count($this->errors) > 0) {
            $_SESSION['errors'] = $this->errors;
            return;
        }
      
        if($this->config["max_dl"] == 0) {
            $this->do_download();
        }
        elseif($this->config["max_dl"] > 0) {
            if($this->background_jobs() >= 0 && $this->background_jobs() < $this->config["max_dl"]) {
                $this->do_download();
            } else {
                $this->errors[] = "Simultaneous downloads limit reached !";
            }
        }
      
        if(isset($this->errors) && count($this->errors) > 0) {
            $_SESSION['errors'] = $this->errors;
            return;
        }
    }
    
    public static function background_jobs()
    {
        return shell_exec("ps aux | grep -v grep | grep -v \"youtube-dl -U\" | grep youtube-dl | wc -l");
    }
    
    public static function max_background_jobs()
    {
        return $this->config["max_dl"];
    }
    
    public static function get_current_background_jobs()
    {
        $bjs = [];
        $dir = new DirectoryIterator($GLOBALS['config']['logPath']);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot() && $fileinfo->isFile() && strpos($fileinfo->getFilename(), "pid_")===0) {
                $outfile = $GLOBALS['config']['logPath']."/".str_replace("pid_", "job_", $fileinfo->getFilename());
                $completefile = $GLOBALS['config']['logPath']."/".str_replace("pid_", "ytdl_", $fileinfo->getFilename());
                if (!file_exists($outfile)) {
                    //No output file exists for job
                    unlink($fileinfo->getPathname());
                    continue;
                }
                $jpid = trim(file_get_contents($fileinfo->getPathname()));
                $ytcmd = explode("\n", $jpid)[1];
                $urltext = explode("\n", $jpid)[2];
                $jpid = explode("\n", $jpid)[0];

                if (!file_exists("/proc/".$jpid)) {
                    // The job has terminated
                    unlink($fileinfo->getPathname());
                    rename($outfile, $completefile);
                    file_put_contents($completefile, "[ytcmd] ". $ytcmd."\n", FILE_APPEND);
                    file_put_contents($completefile, "[yturl] ". $urltext."\n", FILE_APPEND);
                    continue;
                }
                $pidcmd = trim(file_get_contents('/proc/'.$jpid.'/cmdline'));
                // Check that this really is a youtube-dl process and not a process with the same PID as an old job
                if (strpos($pidcmd, $GLOBALS['config']['youtubedlExe']) === false) {
                    // The job has terminated
                    unlink($fileinfo->getPathname());
                    rename($outfile, $completefile);
                    file_put_contents($completefile, "[ytcmd] ". $ytcmd."\n", FILE_APPEND);
                    file_put_contents($completefile, "[yturl] ". $urltext."\n", FILE_APPEND);
                    continue;
                }
                $handle = fopen($outfile, "r");
                $lastline = "";
                $verylastline = "";
                $filename = "TBC";
                $site = "TBC";
                $siteset = false;
                $isaudio = false;
                $listpos = "";
                $playlist = "";
                if (strpos($fileinfo->getFilename(), "_a") !== false) {
                    $isaudio = true;
                }
                if ($handle) {
                    while (($line = fgets($handle)) !== false) {
                        if (strpos($line, '[download] Downloading') !== false) {
                            $listpos = "(".substr($line, 29).")";
                        }
                        if (strpos($line, '[download] Downloading playlist:') !== false) {
                            $playlist = substr($line, 33)."<br />";
                        }
                        if (trim($line) != "") {
                            $lastline = $line;
                        }
                        $verylastline = $line;
                        if (!$siteset) {
                            $siteset = true;
                            $site = explode(" ", $line)[0];
                            $site = str_replace("[", "", $site);
                            $site = str_replace("]", "", $site);
                            $site = ucfirst($site);
                        }
                        if (strpos($line, 'Destination') !== false) {
                            $pos = strrpos($line, '/');
                            $filename = $pos === false ? $line : substr($line, $pos + 1);
                        }
                    }
                    fclose($handle);
                    if ($filename == "TBC") {
                        $lastline = "Getting file information from website"; 
                    } else {
                        $pos = strrpos($lastline, '[download]');
                        $lastline = $pos === false ? "" : trim(substr($lastline, $pos + 11));
                        $filename = urlencode($filename." ".$listpos);
                        $filename = str_replace("%0A", "", $filename);
                        $filename = urldecode($filename);
                        if ($isaudio && strpos($verylastline, '[ffmpeg]') !== false) {
                            $lastline = "Converting to audio. This might take a while.";
                        }
                    }
                    if (strpos($lastline, '100%') !== false || $lastline=="") {
                        $lastline = "In Progress...";
                    }
                    $type = "video";
                    if ($isaudio) {
                        $type = "audio";
                    }

                    $bjs[] = array(
                    'file' => json_encode($playlist.$filename),
                    'site' => json_encode($site),
                    'status' => str_replace("\\n", "", json_encode($lastline)),
                    'type' => json_encode($type),
                    'pid' => json_encode($fileinfo->getFilename()),
                    'url' => json_encode($urltext)
                    );
                }
            }
        }
        return $bjs;
    }
    
    public static function get_finished_background_jobs()
    {
        $bjs = [];
        $dir = new DirectoryIterator($GLOBALS['config']['logPath']);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot() && $fileinfo->isFile() && strpos($fileinfo->getFilename(), "ytdl_")===0) {
                $handle = fopen($fileinfo->getPathname(), "r");
                $lastline = "";
                $verylastline = "";
                $filename = "N/A";
                $site = "N/A";
                $siteset = false;
                $isaudio = false;
                $listpos = "";
                $playlist = "";
                $urltext = "";
                if (strpos($fileinfo->getFilename(), "_a") !== false) {
                    $isaudio = true;
                }
                if ($handle) {
                    while (($line = fgets($handle)) !== false) {
                        if (strpos($line, '[download] Downloading') !== false) {
                            $listpos = substr($line, 29);
                            $listpos = trim(substr($listpos, strpos($listpos, " of")+3));
                        }
                        if (strpos($line, '[download] Downloading playlist:') !== false) {
                            $playlist = substr($line, 33);
                        }
                        $verylastline = $line;
                        if (!$siteset) {
                            $siteset = true;
                            $site = explode(" ", $line)[0];
                            $site = str_replace("[", "", $site);
                            $site = str_replace("]", "", $site);
                            $site = ucfirst($site);
                        }
                        if (strpos($line, '[yturl]') !== false) {
                            $urltext = substr($line, 8);
                        }
                        if (strpos($line, 'Destination') !== false) {
                            $pos = strrpos($line, '/');
                            $filename = $pos === false ? $line : substr($line, $pos + 1);
                        }
                    }
                    fclose($handle);
                    $type = "video";
                    if ($isaudio) {
                        $type = "audio";
                    }
                    $jobstatus = "Completed";
                    if (strpos($fileinfo->getFilename(), "_cancelled")!==false) {
                        $jobstatus = "Cancelled";
                    }
                    if ($playlist != "") {
                        $filename = $playlist." (".$listpos." files)";
                    }
                    if ($filename == "N/A") {
                        $type = "unknown";
                        $jobstatus = "Error";
                    }
                    $bjs[] = array(
                    'file' => json_encode($filename),
                    'site' => json_encode($site),
                    'status' => str_replace("\\n", "", json_encode($jobstatus)),
                    'type' => json_encode($type),
                    'pid' => json_encode($fileinfo->getFilename()),
                    'url' => json_encode($urltext)
                    );
                }
            }
        }
        return $bjs;
    }
    
    public static function kill_one_of_them($fpid)
    {
        $file = $GLOBALS['config']['logPath'].'/'.$fpid;
        if (!file_exists($file)) {
            return;
        }
        $outfile = $GLOBALS['config']['logPath']."/".str_replace("pid_", "job_", $fpid);
        $completed = $GLOBALS['config']['logPath']."/".str_replace("pid_", "ytdl_", $fpid)."_cancelled";
        $jpid = trim(file_get_contents($file));
        $ytcmd = explode("\n", $jpid)[1];
        $urltext = explode("\n", $jpid)[2];
        $jpid = explode("\n", $jpid)[0];

        $pidcmd = trim(file_get_contents('/proc/'.$jpid.'/cmdline'));
        // Check that this really is a youtube-dl process and not a process with the same PID as an old job
        if (strpos($pidcmd, $GLOBALS['config']['youtubedlExe']) !== false) {
            shell_exec("kill ".$jpid);
        }
        rename($outfile, $completed);
        file_put_contents($completed, "[ytcmd] ". $ytcmd."\n", FILE_APPEND);
        file_put_contents($completed, "[yturl] ". $urltext."\n", FILE_APPEND);
        unlink($file);
    }
    
    public static function kill_them_all()
    {
        foreach(glob($GLOBALS['config']['logPath'].'/pid_*') as $file) {
            $jobfile = $str_replace("pid_", "job_", $file);
            $pos = strrpos($jobfile, "job_");
            $completed = substr_replace($jobfile, "ytdl_", $pos, strlen("job_"))."_cancelled";
            rename($jobfile, $completed);
            $jpid = trim(file_get_contents($file));
            $ytcmd = explode("\n", $jpid)[1];
            $jpid = explode("\n", $jpid)[0];
            file_put_contents($completed, "[ytcmd] ". $ytcmd."\n", FILE_APPEND);
            file_put_contents($completed, "[yturl] ". $urltext."\n", FILE_APPEND);
            unlink($file);
        }
      
        exec("ps -A -o pid,comm | grep -v grep | grep youtube-dl | awk '{print $1}'", $output);
      
        if(count($output) <= 0) {
            return;
        }
      
        foreach($output as $p) {
            shell_exec("kill ".$p);
        }
      
        $folder = $GLOBALS['config']['outputFolder'];
      
        if (!$GLOBALS['config']['keepPartialFiles']) {
            foreach(glob($folder.'/*.part') as $file) {
                unlink($file);
            }
        }
    }

    public static function restart_download($fpid) 
    {
        $handle = fopen($GLOBALS['config']['logPath'].'/'.$fpid, "r");
        $ytcmd = "";
        $urltext = "";
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (strpos($line, '[ytcmd]') !== false) {
                    $ytcmd = substr($line, 8);
                }
                if (strpos($line, '[yturl]') !== false) {
                    $urltext = substr($line, 8);
                }
            }
            fclose($handle);
        }
        if ($ytcmd == "") {
            $_SESSION['errors'] = "Could not restart. Log file corrupt.";
            return;
        }
        $suffix = "";
        if (strpos($fpid, "_a") !== false) {
            $suffix = "_a";
        }

        do {
            $fno = "job_".uniqid().$suffix;
        } while (file_exists($GLOBALS['config']['logPath']."/".$fno));

        $fnp = str_replace("job_", "pid_", $fno);
        $ytcmd = trim($ytcmd);
        $cmd = $ytcmd." > ".$GLOBALS['config']['logPath']."/".$fno." & echo $! > ".$GLOBALS['config']['logPath']."/".$fnp;
        passthru($cmd);
        file_put_contents($GLOBALS['config']['logPath']."/".$fnp, $ytcmd."\n", FILE_APPEND);
        file_put_contents($GLOBALS['config']['logPath']."/".$fnp, $urltext."\n", FILE_APPEND);
    }

    public static function clear_one_finished($fpid)
    {
        unlink($GLOBALS['config']['logPath'].'/'.$fpid);
    }
    
    public static function clear_finished()
    {
        foreach(glob($GLOBALS['config']['logPath'].'/ytdl_*') as $file) {
            unlink($file);
        }
    }
    
    private function check_requirements($audio_only)
    {
        $this->check_outuput_folder();
      
        if(isset($this->errors) && count($this->errors) > 0) {
            $_SESSION['errors'] = $this->errors;
            return false;
        }
      
        return true;
    }
    
    private function is_valid_url($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
    
    private function check_outuput_folder()
    {
        if(!is_dir($this->download_path)) {
            //Folder doesn't exist
            if(!mkdir($this->download_path, 0775)) {
                $this->errors[] = "Output folder doesn't exist and creation failed !";
            }
        } else {
            //Exists but can I write ?
            if(!is_writable($this->download_path)) {
                $this->errors[] = "Output folder isn't writable !";
            }
        }
    }

    private function getUniqueFileName($prefix, $suffix, $path)
    {
        do {
            $uid = $prefix.uniqid().$suffix;
        } while (file_exists($path.$uid));
        return $uid;
    }
    
    private function do_download()
    {
        $suffix = "";
        $cmd = $this->config['youtubedlExe'];
        $cmd .= " -o ".$this->download_path."/";
        $cmd .= escapeshellarg("%(title)s-%(uploader)s.%(ext)s");
        $cmd .= " ".$this->dl_format;
      
        if($this->audio_only) {
            $cmd .= " -x";
            $cmd .= " ".$this->audio_format;
            $suffix = "_a";
        }
        $fno = $this->getUniqueFileName("job_", $suffix, $this->config['logPath']."/");
        $fnp = str_replace("job_", "pid_", $fno);
        $urltext = "";
        foreach($this->urls as $url) {
            $cmd .= " ".escapeshellarg($url);
            $urltext .= $url .",";
        }
        $urltext = trim($urltext, ",");
        $cmd .= " --restrict-filenames"; // --restrict-filenames is for specials chars
        $cmd .= " --ignore-errors";
        $logcmd = $cmd;
        $cmd .= " > ".$this->config['logPath']."/".$fno." & echo $! > ".$this->config['logPath']."/".$fnp;
        passthru($cmd);
        file_put_contents($this->config['logPath']."/".$fnp, $logcmd."\n", FILE_APPEND);
        file_put_contents($this->config['logPath']."/".$fnp, $urltext."\n", FILE_APPEND);
    }
}
?>
