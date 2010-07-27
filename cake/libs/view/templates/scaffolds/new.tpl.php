<?php
/* SVN FILE: $Id: new.tpl.php,v 1.3 2006/06/20 18:46:46 zoeshum Exp $ */

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
<h1>New <?php echo Inflector::humanize($this->name)?></h1>

<?php
if(is_null($this->plugin))
{
	$path = '/';
}
else
{
	$path = '/'.$this->plugin.'/';
}
echo $html->formTag($path. Inflector::underscore($this->name).'/create');
echo $form->generateFields( $fieldNames );
echo $form->generateSubmitDiv( 'Add' )
?>
</form>
<ul class='actions'>
<?php
echo "<li>".$html->link('List  '.Inflector::humanize($this->name), $path.$this->viewPath.'/index')."</li>";
?>
</ul>