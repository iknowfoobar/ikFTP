<?php  
  /**
   * FTP Class
   *
   * Allows connections to FTP servers
   *
   * @author Giles Smith
   * @copyright Copyright (c) 2010
   */
  class ftp
  {    
    /**
     * @param array $errors An array of any errors
     */
    public $errors = array();
    
    private $conn_id;
    private $server;
    private $username;
    private $password;
    private $port;
    private $passive;
    
    /**
     * Default Constructor
     *
     * @return bool
     * @access public
     * @param string $server The server hostname to connect to
     * @param string $username The username required to access the FTP server
     * @param string $password The password required to access the FTP server
     * @param int $port The port number to connect to the FTP server on
     * @param bool $passive Whether or not to use a passive or active connection
     */
    public function __construct($server, $username, $password, $port = 21, $passive = FALSE)
    {
      $this->server     = $server;
      $this->username   = $username;
      $this->password   = $password;
      $this->port       = $port;
      $this->passive    = $passive;
      return TRUE;
    }
    
    /**
     * Upload a local file to the remote server
     *
     * @access public
     * @param string $source_file The local file to upload
     * @param string $destination_file The remote location and name of the file
     * @param string $transfer_mode optional Defaults to Binary connections but can use FTP_ASCII
     */
    public function upload($source_file, $destination_file, $transfer_mode = FTP_BINARY)
    {      
      if(!$this->open_connection()){return FALSE;}
      
      // check local file
      if(!is_file($source_file))
      {
        $this->errors[] = "Unable to find local file to send";
        return FALSE;
      }
      
      // attempt to send file
      if(!@ftp_put($this->conn_id, $destination_file, $source_file, $transfer_mode))
      {
        $this->errors[] = "Unable to send file to remote server, does destination folder exist?";
        $this->close_connection();
        return FALSE;
      }
      
      $this->close_connection();
      return TRUE;
    }
    
    /**
     * Download a a file from remote server to local file
     *
     * @access public
     * @param string $source_file The remote file
     * @param string $destination_file The local file to create
     * @param string $transfer_mode optional Defaults to Binary connections but can use FTP_ASCII
     */
    public function download($source_file, $destination_file, $transfer_mode = FTP_BINARY)
    {
      if(!$this->open_connection()){return FALSE;}
      
      // download file
      if(!@ftp_get($this->conn_id, $destination_file, $source_file, $transfer_mode))
      {
        $this->errors[] = "Unable to download file, does local folder exist";
        $this->close_connection();
        return FALSE;
      }
      
      $this->close_connection();
      return TRUE;
    }  
    
    /**
     * Deletes a remote file
     *
     * @access public
     * @param string $file The remote file to delete
     */
    public function delete_file($file = '')
    {
      if(!$this->open_connection()){return FALSE;}
      
      if(!@ftp_delete($this->conn_id, $file))
      {
        $this->errors[] = "Unable to delete remote file, have you checked permissions.";
        $this->close_connection();
        return FALSE;
      }
      
      $this->close_connection();
      return TRUE;
    }
    
    /**
     * Rename or move a file or a directory
     * 
     *
     * @return bool
     * @access public
     * @param string $source_file The file or folder to be renamed/moved
     * @param string $renamed_file The destination or new name of the file/folder
     */
    public function rename_or_move($source_file, $renamed_file)
    {
      if(!$this->open_connection()){return FALSE;}
      
      if(!@ftp_rename($this->conn_id, $source_file, $renamed_file))
      {
        $this->errors[] = "Unable to rename/move file";
        $this->close_connection();
        return FALSE;
      }
      
      $this->close_connection();
      return TRUE;
    }
    
    /**
     * Create a remote directory
     *
     * @return bool
     * @access public
     * @param string $dir The path of the remote directory to create
     */
    public function create_dir($dir)
    {
      if(!$this->open_connection()){return FALSE;}
      
      if(ftp_mkdir($this->conn_id, $dir) === FALSE)
      {
        $this->errors[] = "Unable to create remote directory";
        $this->close_connection();
        return FALSE;
      }
      
      $this->close_connection();
      return TRUE;
    }
    
    /**
     * Delete a remote directory
     *
     * @return bool
     * @access public
     * @param string $dir The path of the remote directory to delete
     */
    function delete_dir()
    {
      if(!$this->open_connection()){return FALSE;}
      
      if(!@ftp_rmdir($this->conn_id, $dir))
      {
        $this->errors[] = "Unable to delete remote directory";
        $this->close_connection();
        return FALSE;
      }
      
      $this->close_connection();
      return TRUE;
    }
    
    /**
     * Set permissions on a file or directory
     *
     * @return bool
     * @access public
     * @param string $file The file or directory to modify
     * @param int $chmod optional The permissions to apply Default 0644
     */
    function set_permissions($file, $chmod = 0644)
    {
      if(!$this->open_connection()){return FALSE;}
      
      if (!function_exists('ftp_chmod'))
      {
        if(!@ftp_site($this->conn_id, sprintf('CHMOD %o %s', $chmod, $file)))
        {
          $this->errors[] = "Unable to modify permissions";
          $this->close_connection();
          return FALSE;
        }
      }
      else
      {
        if(!@ftp_chmod($this->conn_id, $chmod, $file))
        {
          $this->errors[] = "Unable to modify permissions";
          $this->close_connection();
          return FALSE;
        }
      }
      
      $this->close_connection();
      return TRUE;
    }
    
    /**
     * Get the size in bytes of a remote file
     * Can be used to check if a file exists
     *
     * @return bool|int FALSE if file doesn't exist or the number of bytes
     * @access public
     * @param string $filename The remote file to check
     */
    function file_size($filename)
    {
      if(!$this->open_connection()){return FALSE;}
      
      $file_size = @ftp_size($this->conn_id, $filename);
      
      if(!$file_size or $file_size == -1)
      {
        $this->errors[] = "Unable to find remote file";
        $this->close_connection();
        return FALSE;
      }
      
      $this->close_connection();
      return $file_size;
    }
    
    /**
     * Checks whether a directory exists by trying to navigate to it
     *
     * @return bool
     * @access public
     * @param string $dir The directory to check
     */
    function dir_exists($dir)
    {
      if(!$this->open_connection()){return FALSE;}
      
      if(!@ftp_chdir($this->conn_id, $dir))
      {
        $this->close_connection();
        return FALSE;
      }
      
      $this->close_connection();
      return TRUE;
    }
    
    /**
     * Returns the contents of a directory
     *
     * @return array|bool An array of files or a FALSE on error
     * @access public
     * @param string $dir The directory to read
     */
    function dir_contents($dir)
    {
      $this->open_connection();
      
      $f = @ftp_nlist($this->conn_id, $dir);
      
      if(empty($f))
      {
        $this->errors[] = "Unable to read remote directory";
        $this->close_connection();
        return FALSE;
      }
      
      $this->close_connection();
      return $f;
    }
    
    /**
     * Attempts to open a connection to the remote server and authenticate the user
     * Also sets the connection mode
     *
     * @return bool
     * @access private
     */
    private function open_connection()
    {
      if(!$conn_id = @ftp_connect($this->server, $this->port))
      {
        $this->errors[] = "Unable to connect to remote server";
      }
      
      if(!@ftp_login($conn_id, $this->username, $this->password))
      {
        $this->errors[] = "Connected to server but unable to authenticate user";
      }
      
      if(!@ftp_pasv($conn_id, $this->passive))
      {
        $this->errors[] = "Unable to set passive mode";
      }
      
      if(empty($this->errors))
      {
        $this->conn_id = $conn_id;
        return TRUE;
      }
      
      return FALSE;
    }
    
    /**
     * Attempts to close the connection
     *
     * @return bool
     * @access private
     */
    function close_connection()
    {
      if(!@ftp_close($this->conn_id)){return FALSE;}
      $this->conn_id = "";
      return TRUE;
    }
  }
?>
