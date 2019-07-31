<?php

if(class_exists('WP_GitHub_Updater')) return;

class WP_GitHub_Updater{
	
	private $file;
	
	function __construct($file){
		
		$this->file = $file;
		
		$this->master_file = basename($file);
		
		$this->folder = dirname($this->file);
		
		$this->local_meta = \get_plugin_data($this->file);
		
		$this->name = $this->local_meta['Name'];
		
		$this->github_repo = str_replace('https://github.com/','',$this->local_meta['PluginURI']);
		
		$this->current_version = $this->local_meta['Version'];
		
		$this->remote_meta = \get_plugin_data('https://raw.githubusercontent.com/' . $this->github_repo . '/master/' . $this->master_file);
	
		$this->remote_version = $this->remote_meta['Version'];

		$this->branch = (isset($this->local_meta['Branch']) ? $this->local_meta['Branch'] : "master");		

		add_filter( 'plugin_row_meta', array($this,'updater_links'), 10, 4 );
		add_action( 'wp_ajax_plugin_updater',array($this, 'ajax_plugin_updater') );
		add_action( 'admin_notices', array($this,'show_messages') );
	}
	
	
	function updater_links( $links_array, $plugin_file_name, $plugin_data, $status ){
	 
		if( strpos( $plugin_file_name, basename($this->file) ) ) {
			
			$query = http_build_query(array(
				'action'=>'plugin_updater',
				'file'=>urlencode($this->file),
			));

			if($this->current_version < $this->remote_version){
				$links_array[] = '<a class="button button-small button-primary" href="' . admin_url('admin-ajax.php?') . $query . '">New Update Available ' . $this->remote_version  . '</a>';
			}else{
				$links_array[] = '<a class="button button-small button-primary" href="' . admin_url('admin-ajax.php?') . $query . '">Download ' . $this->remote_version  . '</a>';
			}
		}
	 
		return $links_array;
	}
	
	function show_messages(){
		
		if(isset($_GET['updater'])){
			
			if($_GET['updater'] == 'fail'){
				$class = 'notice notice-error';
				$message = __( 'There was a problem updating plugin ' . $this->name, 'updater' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
			}
			
			
			if($_GET['updater'] == 'success'){
				$class = 'notice notice-success';
				$message = __( 'Plugin ' . $this->name . ', Successfully updated to version ' . $this->current_version, 'updater' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 				
			}			
		}
		
	}
	
	function remove_local_plugin(){
		
		
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->folder, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $fileinfo) {
			$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
			$todo($fileinfo->getRealPath());
		}

		rmdir($this->folder);
		
	}
	
	function ajax_plugin_updater(){
		
	
		if($this->file == urldecode($_GET['file'])){

			try{

				$ch = curl_init();
				$source = 'https://github.com/' . $this->github_repo . '/archive/' . $this->branch . '.zip'; 
				curl_setopt($ch, CURLOPT_URL, $source);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				$data = curl_exec ($ch);
				curl_close ($ch);

				$destination = wp_upload_dir()['basedir'] . '/' . $this->branch . '.zip'; 
				
				$file = fopen($destination, "w+");
				fputs($file, $data);
				fclose($file);

				$zip = new ZipArchive;
				$res = $zip->open($destination); 
				
				if ($res === TRUE) {
					$zip->extractTo(WP_PLUGIN_DIR); 
					$zip->close();

					$this->remove_local_plugin();
					rename($this->folder . '-' . $this->branch, $this->folder );

				} else {
					'unzip failed;';
				}
				
				unlink($destination);
				
				wp_redirect( admin_url('plugins.php?updater=success') );
				exit;
			
			}
			
			catch (Exception $e){
				
				wp_redirect( admin_url('plugins.php?updater=fail') );
				exit;
				
			}
			

		}
		
	
		
	}
	
	
	
		
}

?>