<?php
/* SVN FILE: $Id: list.tpl.php,v 1.3 2006/06/20 18:46:46 zoeshum Exp $ */

/**
 * Base controller class.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.templates.scaffolds
 * @since			CakePHP v 0.10.0.1076
 * @version			$Revision: 1.3 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2006/06/20 18:46:46 $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<h1>List <?php echo Inflector::humanize($this->name)?></h1>

<?php
$model = ucwords(Inflector::singularize($this->name));
$modelKey = $model;
$humanName = Inflector::humanize($this->name);
$humanSingularName = Inflector::singularize( $humanName );
if(is_null($this->plugin))
{
	$path = '/';
}
else
{
	$path = '/'.$this->plugin.'/';
}
if(!empty($this->controller->{$model}->alias))
{
	foreach ($this->controller->{$model}->alias as $key => $value)
	{
		$alias[] = $key;
	}
}
?>
<table class="inav" cellpadding="0" cellspacing="0">
<thead>
<tr>
<?php
foreach ($fieldNames as $fieldName)
{
?>
	<th><?php echo $fieldName['prompt'];?></th>
<?php
}
?>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php
$iRowIndex = 0;
if(is_array($data))
{
	foreach ($data as $row)
	{
		if($iRowIndex++ % 2 == 0)
		{
			echo "<tr>";
		}
		else
		{
			echo "<tr class='altRow'>";
		}

		$count = 0;
		foreach($fieldNames as $field=>$value)
		{
?>
			<td>
<?php
			if(isset($value['foreignKey']))
			{
				$otherModelKey = Inflector::underscore($value['modelKey']);
				$otherControllerName = $value['controller'];
				$otherModelObject =& ClassRegistry::getObject( $otherModelKey );
				if(is_object($otherModelObject))
				{
					$displayText = $row[$alias[$count]][ $otherModelObject->getDisplayField() ];
				}
				else
				{
					$displayText = $row[$alias[$count]][$field];
				}
				echo $html->link( $displayText, $path.Inflector::underscore($otherControllerName)."/show/".$row[$modelKey][$field] );
				$count++;
			}
			else
			{
				echo $row[$modelKey][$field];
			}
?>
			</td>
<?php
		}
?>
		<td class="listactions"><?php echo $html->link('View',$path.$this->viewPath."/show/{$row[$modelKey][$this->controller->{$model}->primaryKey]}/")?>
								<?php echo $html->link('Edit',$path.$this->viewPath."/edit/{$row[$modelKey][$this->controller->{$model}->primaryKey]}/")?>
								<?php echo $html->link('Delete',$path.$this->viewPath."/delete/{$row[$modelKey][$this->controller->{$model}->primaryKey]}/")?>
		</td>
		</tr>
<?php
	}
}
?>
</tbody>
</table>
<ul class="actions">
	<li><?php echo $html->link('New '.$humanSingularName, $path.$this->viewPath.'/add'); ?></li>
</ul>