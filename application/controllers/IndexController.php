<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
		$formFileUpload = new Application_Form_FileUpload();
		$this->view->formFileUpload = $formFileUpload;
		
		if($this->getRequest()->isPost())
		{
			if($formFileUpload->isValid($_POST))
			{
				$adapter = new Zend_File_Transfer_Adapter_Http();
				$path = APPLICATION_PATH.'/../public/upload/';
				$folder = new Zend_Search_Lucene_Storage_Directory_Filesystem($path);
				$adapter->setDestination($path);
			
				foreach($adapter->getFileInfo() as $file => $info) 
				{
					if($adapter->isUploaded($file)) 
					{
						$name   = $adapter->getFileName($file);
						$extName = $this->_getExtension($info['name']);
						$adapter->receive($file);
						if(strtolower($extName) == 'csv')
							$fridgeData = $this->_processCSV($path.$info['name']);
						else
							$recipesData = $this->_processJSON($path.$info['name']);                                                         
					}
				}
				$cooking = $this->_getCooking($fridgeData, $recipesData);
				
				if($cooking)
					$this->view->dinner = $cooking['name'];
				else
					$this->view->dinner = 'Order Takeout';
			}
		}

    }

	protected function _getExtension($fileName)
	{
		$exts = split("[\.]", $fileName);
		$n = count($exts) -1;
		$exts = $exts[$n];
		return $exts;
	}

	protected function _processCSV ($filename) 
	{
		$data = array();

		if (($handle = fopen($filename, "r")) !== FALSE) 
		{
			$item = null;
			while (($line = fgetcsv($handle, 1000, ",")) !== FALSE) 
			{
				$item['name'] = $line[0];
				$item['quantity'] = $line[1];
				$item['unit'] = $line[2];
				$item['useBy'] = $line[3];
				$data[] = $item;
			}
			fclose($handle);
		}

		return $data;
	}
	
	protected function _processJSON ($filename) 
	{
		$data = array();
		$jsonString = file_get_contents($filename);
		$data = Zend_Json::decode($jsonString);
		return $data;
	 }

	protected function _getCooking ($fridge, $recipes) 
	{
		$availableRecipes = array();
		foreach ($recipes as $recipe)
		{
			$available = true;
			$closest = null;
			foreach($recipe['ingredients'] as $ingredient)
			{
				if (!$useBy = $this->_itemAvailable($fridge, $ingredient)) 
				{
					$available = false;
				}
				else
				{
					if(!$closest)
						$closest = $useBy;
					else if($closest && $closest > $useBy)
						$closest = $useBy;
				}
			}
			
			if($available)
			{
				$recipe['closest'] = $closest;
				$availableRecipes[] = $recipe;
			}
		}

		return $recipeWithClosest = $this->_getClosest($availableRecipes);
	}

	protected function _itemAvailable ($fridge, $ingredient) 
	{
		$availableRecipes = array();
		$useByTimeStamp = null;
		foreach ($fridge as $f)
		{
			if($f['name'] == $ingredient['item'])
			{
				$useByTimeStamp = strtotime(str_replace('/', '-', $f['useBy']));
				if($useByTimeStamp > time() && $f['quantity'] >= $ingredient['amount'])
					return $useByTimeStamp;
			}
		}
		return false;
	}

	protected function _getClosest ($recipes) 
	{
		$closestRecipe = null;
		foreach($recipes as $recipe)
		{
			if(!$closestRecipe)
				$closestRecipe = $recipe;
			else if($closestRecipe && $closestRecipe['closest'] > $recipe['closest'])
				$closestRecipe = $recipe;
			
		}

		return $closestRecipe;
	}
	
}
 
