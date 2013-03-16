<?php

class installer {

	private $_divisionID = 0;
	private $_moduleID = 0;
	private $_moduleConfigID = 0;

	/*
	array(
		'xName'=>'pgn_',
		'xKey'=>'',
		'xUnicKey'=>'',
		'xParentID'=>0,
		'xEnabled'=>1,
		'xPriority'=>0,
		'xTemplate'=>'',
		'xLinkDivisionUKey'=>''
	);
	*/
	public function saveDivision($data)
	{
		$divisionID = 0;
		$xName = $data['xName'];
		$q = "SELECT xID FROM SC_divisions WHERE xName LIKE '$xName'";
		if ( $r = mysql_query($q) )
		{
			$row = mysql_fetch_assoc($r);
			$divisionID = $row['xID'];
		}
		
		if ( $divisionID == 0 )
		{
			$this->_insert($data,'SC_divisions');
			$divisionID = $this->_lastInsertId('SC_divisions');
		}
		return $divisionID;
	}
	
	/*
	array(
		'xDivisionID'=>0,
		'xInterface'=>'',
		'xPriority'=>0,
		'xInheritable'=>0,
	);
	*/
	public function saveDivisionInterface($data)
	{
		$xInterface = $data['xInterface'];
		$q = "SELECT COUNT(*) as c FROM SC_division_interface WHERE xInterface LIKE '$xInterface'";
		$c = array_shift( mysql_fetch_assoc( mysql_query($q) ) );
		if ( $c == 0 )
			$this->_insert($data,'SC_division_interface');
	}
	
	/*
	array(
		'ModuleID'=>0,
		'ModuleVersion'=>1,
		'ModuleClassName'=>'',
		'ModuleClassFile'=>'',
	);
	*/
	public function saveModule($data)
	{
		$name = $data['ModuleClassName'];
		$moduleID = $this->_getModuleIdByClassName($name);
		if ( $moduleID == 0 )
		{
			$this->_insert($data,'SC_modules');
			$moduleID = $this->_lastInsertId('SC_modules');
		}
		return $moduleID;
	}
	
	private function _getModuleIdByClassName($name)
	{
		$moduleID = 0;
		
		$q = "SELECT ModuleID FROM SC_modules WHERE `ModuleClassName` LIKE '$name'";
		if ( $r = mysql_query($q) )
		{
			$row = mysql_fetch_assoc($r);
			$moduleID = $row['ModuleID'];
		}
		
		return $moduleID;
	}
	
	/*
	array(
		'ModuleConfigID'=>0,
		'ModuleID'=>0,
		'ModuleClassName'=>'',
		'ConfigKey'=>'',
		'ConfigTitle'=>'',
		'ConfigDescr'=>'',
		'ConfigInit'=>1002,
		'ConfigEnabled'=>1,
	);
	*/
	public function saveModuleConfigs($data)
	{
		$moduleID = $data['ModuleID'];
		$ModuleConfigID = $this->_getModuleConfigsId($moduleID);
		
		if ( $ModuleConfigID == 0 )
		{
			$this->_insert($data,'SC_module_configs');
			$ModuleConfigID = $this->_lastInsertId('SC_module_configs');
		}
		return $ModuleConfigID;
	}
	
	
	/*
	array(
		'moduleID'=>0,
		'interface'=>'',
		'divisionID'=>0
	);
	*/
	public function setDivisionInterface($data)
	{
		extract($data);
		$ModuleConfigID = $this->_getModuleConfigsId($moduleID);
		
		$d = array(
			'xDivisionID'=>$divisionID,
			'xInterface'=>$ModuleConfigID.'_'.$interface,
			'xPriority'=>0,
			'xInheritable'=>0,
		);
		$this->saveDivisionInterface($d);
	}
	
	public function getModuleIDByClassName($class_name)
	{
		$ModuleID = 0;
		
		$q = "SELECT ModuleID FROM SC_modules WHERE ModuleClassName LIKE '$class_name'";
		if ( $r = mysql_query($q) )
		{
			$row = mysql_fetch_assoc($r);
			$ModuleID = $row['ModuleID'];
		}
		
		return $ModuleID;
	}
	
	private function _getModuleConfigsId($moduleID)
	{
		$ModuleConfigID = 0;
		
		$q = "SELECT `ModuleConfigID` FROM SC_module_configs WHERE `ModuleID`=$moduleID";
		if ( $r = mysql_query($q) )
		{
			$row = mysql_fetch_assoc($r);
			$ModuleConfigID = $row['ModuleConfigID'];
		}
		
		return $ModuleConfigID;
	}
	
	/*
	array(
		'id'=>'',
		'lang_id'=>1,
		'value'=>'',
		'group'=>'hidden'|'front'|'back'|'general',
		'subgroup'=>'gen',
	);
	*/
	public function saveLocal($data)
	{
		$id = $data['id'];
		$q = "SELECT COUNT(*) FROM SC_local WHERE id LIKE '$id'";
		$c = array_shift( mysql_fetch_assoc( mysql_query($q) ) );
		if ( $c == 0 )
			$this->_insert($data,'SC_local');
	}
	
	/*
	array(
		'xName'=>'',
		'xUnicKey'=>'',//
		'xTemplate'=>'',//
		'xParentID'=>0,
		'moduleID'=>0,
		'interface'=>'',
		'xLinkDivisionUKey'=>''//
		'local_value'=>'',
		'local_group'=>'back'|'front',
	);
	*/
	public function connectingInterface($data)
	{
		$divisionID = 0;
		
		extract($data);
		$data = array(
			'xName'=>$xName,
			'xKey'=>'',
			'xUnicKey'=> ( isset($xUnicKey) ) ? $xUnicKey : '',
			'xParentID'=>$xParentID,
			'xEnabled'=>1,
			'xPriority'=>0,
			'xTemplate'=>( isset($xTemplate) ) ? $xTemplate : '',
			'xLinkDivisionUKey'=> ( isset($xLinkDivisionUKey) ) ? $xLinkDivisionUKey : ''
		);
		$divisionID = $this->saveDivision($data);

		if ( empty($xLinkDivisionUKey) )
		{
			$data = array(
				'moduleID'=>$moduleID,
				'interface'=>$interface,
				'divisionID'=>$divisionID
			);
			$this->setDivisionInterface($data);
		}

		$data = array(
			'id'=>$xName,
			'lang_id'=>1,
			'value'=>$local_value,
			'group'=>$local_group,
			'subgroup'=>'gen',
		);
		$this->saveLocal($data);
		
		return $divisionID;
	}
	
	/*
	array(
		'xInterfaceCaller'=>'',
		'xInterfaceCalled'=>'',
		'xPriority'=>0
	);
	*/
	public function saveCpt($data)
	{
		$q = "SELECT COUNT(*) FROM SC_interface_interfaces WHERE xInterfaceCalled LIKE '{$data['xInterfaceCalled']}'";
		$c = array_shift( mysql_fetch_assoc( mysql_query($q) ) );
		if ( $c == 0 )
			$this->_insert($data,'SC_interface_interfaces');
	}
	
	public function getDivisionIdByUnicKey($xUnicKey)
	{
		$divisionID = 1;
		
		$q = "SELECT xID FROM SC_divisions WHERE xUnicKey LIKE '$xUnicKey'";
		if ( $r = mysql_query($q) )
		{
			$row = mysql_fetch_assoc($r);
			$divisionID = $row['xID'];
		}
		
		return $divisionID;
	}
	
	/*
	$data = array(
		'file_path' => DIR_ROOT.'\published\SC\html\scripts\classes\class.product.php',
		'pattern' => '(var\s+\$slug;)',
		'replacement' => '$1\r\n\tvar $product_type_id;',
		'task' => 'var $product_type_id;'
	);
	*/
	public function insertIntoFile($data)
	{
		extract($data);
		
		$result = false;
		$content = file_get_contents($file_path);
		if ( !empty($content) && strpos($content,$test) === false )
		{
			$content = iconv('ASCII//TRANSLIT','UTF-8',$content);
			//echo $content;exit;
			//preg_match('/'.$pattern.'/i', $content, $m);
			//print_r($m);exit;
			$content = preg_replace('/'.$pattern.'/i', $replacement, $content);
			//echo $content;exit;
			if ( strpos($content,$test) !== false )
				$result = file_put_contents($file_path, $content);
		}

		return $result;
	}
	
	/*
	Первое поле data - ключ
	Второе поле data - поле поиска
	*/
	public function insert($data,$table)
	{
		$id = 0;
		
		$i = 0;
		foreach ( $data as $k=>$v )
		{
			switch ( $i )
			{
				case 0 :
					$id_field = $k;
					unset($data[$k]);
					$i++;
					break;
				case 1 :
					$field = $k;
					$value = $v;
					$i++;
					break;
			}
			$add_where = ( $k == '__add_where' ) ? $v : '';
		}
		unset($data['__add_where']);
		
		$like = ( is_int($value) ) ? '=' : $like = 'LIKE';
		$q = "SELECT `$id_field` FROM $table WHERE `$field` $like '$value' $add_where";
		if ( $r = mysql_query($q) )
		{
			if ( $row = mysql_fetch_assoc($r) )
				$id = $row[$id_field];
		}
		if ( $id == 0 )
		{
			$this->_insert($data,$table);
			$id = $this->_lastInsertId($table);
		}
		
		return $id;
	}
	
	private function _insert($data,$table)
	{
		if ( $table && is_array($data) )
		{
			$names = '`'.implode('`,`',array_keys($data)).'`';
			$values = "'".implode("','",array_values($data))."'";
			echo "INSERT INTO `$table` ($names) VALUES ($values)<br>";
			mysql_query("INSERT INTO `$table` ($names) VALUES ($values)");
		}
	}
	
	private function _lastInsertId($table)
	{
		$id = 0;
		
		$q = "SELECT LAST_INSERT_ID() as id FROM $table";
		if ( $r = mysql_query($q) )
		{
			$row = mysql_fetch_assoc($r);
			$id = $row['id'];
		}
		
		return $id;
	}

}