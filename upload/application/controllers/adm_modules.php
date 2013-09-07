<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Game AdminPanel (АдминПанель)
 *
 * 
 *
 * @package		Game AdminPanel
 * @author		Nikita Kuznetsov (ET-NiK)
 * @copyright	Copyright (c) 2013, Nikita Kuznetsov (http://hldm.org)
 * @license		http://www.gameap.ru/license.html
 * @link		http://www.gameap.ru
 * @filesource
*/

/**
 * Управление модулями
 *
 * Контроллер управляет выделенными серверами, игровыми серверами,
 * играми и игровыми модификациями.
 * Позволяет производить следующие действия: добавление, редактирование,
 * удаление, дублирование игровой модификации.
 * 
 * Установку игровых серверов производит модуль cron, adm_servers лишь
 * делает запись о том, что сервер нужно установить.
 * 
 * Переустановка игровых серверов делается заданием значения 0 поля
 * installed таблицы servers.
 *
 * @package		Game AdminPanel
 * @category	Controllers
 * @author		Nikita Kuznetsov (ET-NiK)
 * @sinse		0.8
 */
 
class Adm_modules extends CI_Controller {
	
	var $tpl_data = array();
	
	public function __construct()
    {
        parent::__construct();
        $this->load->model('users');
        
        if ($this->users->check_user()) {
			
			/* Есть ли у пользователя права */
			if (FALSE == $this->users->auth_data['is_admin']) {
				show_404();
			}
			
			//Base Template
			$this->tpl_data['title'] 	= '';
			$this->tpl_data['heading'] 	= '';
			$this->tpl_data['content'] 	= '';
			
			$this->tpl_data['menu'] = $this->parser->parse('menu.html', $this->tpl_data, TRUE);
		}
	}
	
	// ---------------------------------------------------------------------
	
	// Отображение информационного сообщения
    function _show_message($message = FALSE, $link = FALSE, $link_text = FALSE)
    {
        
        if (!$message) {
			$message = lang('error');
		}
		
        if (!$link) {
			$link = 'javascript:history.back()';
		}
		
		if (!$link_text) {
			$link_text = lang('back');
		}

        $local_tpl_data['message'] = $message;
        $local_tpl_data['link'] = $link;
        $local_tpl_data['back_link_txt'] = $link_text;
        $this->tpl_data['content'] = $this->parser->parse('info.html', $local_tpl_data, TRUE);
        $this->parser->parse('main.html', $this->tpl_data);
    }
	
	// ---------------------------------------------------------------------
	
	function _update_list()
	{
		$this->load->helper('directory');
		
		if ($map = directory_map(APPPATH . 'modules')) {
			
			/* Очищаем список модулей из базы */
			$this->gameap_modules->clean_modules();
			
			foreach($map as $key => $value) {
				
				if (!is_array($value)) {
					/* Это файл */
					continue;
				}
				
				if (!is_dir(APPPATH . 'modules/' . $key)) {
					/* Это не директория */
					continue;
				}
				
				/* Поиск файла с информацией о модулей */
				if (file_exists(APPPATH . 'modules/' . $key . '/module_info.php')) {
					
					/* Инклудим файл с инфой */
					include APPPATH . 'modules/' . $key . '/module_info.php';
					
					$sql_data['short_name'] 	= $key;
					$sql_data['name']			= $module_info['name'];
					$sql_data['description']	= $module_info['description'];
					$sql_data['version']		= $module_info['version'];
					$sql_data['show_in_menu']	= (int)(bool)$module_info['show_in_menu'];
					$sql_data['access']			= $module_info['access'];
					$sql_data['developer']		= $module_info['developer'];
					$sql_data['site']			= $module_info['site'];
					$sql_data['email']			= $module_info['email'];
					$sql_data['copyright']		= $module_info['copyright'];
					$sql_data['license']		= $module_info['license'];
					
					$this->gameap_modules->add_module($sql_data);
					
				}
			}
			return TRUE;
		} else {
			return FALSE;
		}
		
	}
	
	// ---------------------------------------------------------------------
	
	public function index()
	{
		$local_tpl_data['modules_list'] = ($this->gameap_modules->modules_data) ? $this->gameap_modules->modules_data : array();
		
		$this->tpl_data['content'] = $this->parser->parse('adm_modules/modules_list.html', $local_tpl_data, TRUE);
		$this->parser->parse('main.html', $this->tpl_data);
	}
	
	// ---------------------------------------------------------------------
	
	function info($module_id)
	{
		
		if (!$this->gameap_modules->modules_data) {
			$this->_show_message('Module not found');
			return FALSE;
		}
		
		$local_tpl_data = array();
		$module_found = FALSE;
		
		/*
		 * Т.к список модулей уже получен, то 
		 * нужно лишь прогнать массив и найти в нем
		 * нужный нам модуль
		 */
		foreach($this->gameap_modules->modules_data as $module) {
			if ($module_id == $module['short_name']) {
				$module_found = TRUE;
				
				$local_tpl_data['module_name'] 			= $module['name'];
				$local_tpl_data['module_description'] 	= $module['description'];
				$local_tpl_data['module_version'] 		= $module['version'];
				$local_tpl_data['module_copyright'] 	= $module['copyright'];
				$local_tpl_data['module_license'] 		= auto_link($module['license']);
				$local_tpl_data['module_developer'] 	= $module['developer'];
				$local_tpl_data['module_email'] 		= $module['email'];
				$local_tpl_data['module_site'] 			= $module['site'];
				
				break;
			}
		}
		
		if (!$module_found) {
			$this->_show_message('Module not found');
			return FALSE;
		}

		$this->tpl_data['content'] = $this->parser->parse('adm_modules/module_info.html', $local_tpl_data, TRUE);
		
		$this->parser->parse('main.html', $this->tpl_data);
	}
	
	// ---------------------------------------------------------------------
	
	public function install()
	{
		$this->tpl_data['content'] = 'Функция в разработке';
		$this->parser->parse('main.html', $this->tpl_data);
	}
	
	// ---------------------------------------------------------------------
	
	public function update_list()
	{
		if ($this->_update_list()) {
			$this->_show_message('Modules list updated');
			return TRUE;
		} else {
			$this->_show_message('Modules list update failure');
			return FALSE;
		}
	}

}
