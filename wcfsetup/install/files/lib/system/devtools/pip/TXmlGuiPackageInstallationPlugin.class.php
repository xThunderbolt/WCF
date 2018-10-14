<?php
namespace wcf\system\devtools\pip;
use wcf\data\devtools\project\DevtoolsProject;
use wcf\data\IEditableCachedObject;
use wcf\system\form\builder\field\IFormField;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\IFormNode;
use wcf\system\package\PackageInstallationDispatcher;
use wcf\system\WCF;
use wcf\util\DOMUtil;
use wcf\util\StringUtil;
use wcf\util\XML;

/**
 * Provides default implementations of the methods of the
 * 	`wcf\system\devtools\pip\IGuiPackageInstallationPlugin`
 * interface for an xml-based package installation plugin.
 * 
 * @author	Matthias Schmidt
 * @copyright	2001-2018 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Devtools\Pip
 * @since	3.2
 * 
 * @property	PackageInstallationDispatcher|DevtoolsPackageInstallationDispatcher	$installation
 */
trait TXmlGuiPackageInstallationPlugin {
	/**
	 * dom element representing the original data of the edited entry
	 * @var	null|\DOMElement
	 */
	protected $editedEntry;
	
	/**
	 * type of the currently handled pip entries
	 * @var	null|string
	 */
	protected $entryType;
	
	/**
	 * Adds a new entry of this pip based on the data provided by the given
	 * form.
	 *
	 * @param	IFormDocument		$form
	 */
	public function addEntry(IFormDocument $form) {
		$xml = $this->getProjectXml();
		$document = $xml->getDocument();
		
		$newElement = $this->createXmlElement($document, $form);
		$this->insertNewXmlElement($xml, $newElement);
		
		$this->saveObject($newElement);
		
		/** @var DevtoolsProject $project */
		$project = $this->installation->getProject();
		
		$xml->write($this->getXmlFileLocation($project));
	}
	
	/**
	 * Creates a new XML element for the given document using the data provided
	 * by the given form and return the new dom element.
	 * 
	 * @param	\DOMDocument		$document
	 * @param	IFormDocument		$form
	 * @return	\DOMElement
	 */
	abstract protected function createXmlElement(\DOMDocument $document, IFormDocument $form);
	
	/**
	 * Edits the entry of this pip with the given identifier based on the data
	 * provided by the given form and returns the new identifier of the entry
	 * (or the old identifier if it has not changed).
	 *
	 * @param	IFormDocument		$form
	 * @param	string			$identifier
	 * @return	string			new identifier
	 */
	public function editEntry(IFormDocument $form, $identifier) {
		$xml = $this->getProjectXml();
		$document = $xml->getDocument();
		
		// add updated element
		$newElement = $this->createXmlElement($document, $form);
		
		// replace old element
		$element = $this->getElementByIdentifier($xml, $identifier);
		DOMUtil::replaceElement($element, $newElement, false);
		
		$this->saveObject($newElement, $element);
		
		/** @var DevtoolsProject $project */
		$project = $this->installation->getProject();
		
		$xml->write($this->getXmlFileLocation($project));
		
		return $this->getElementIdentifier($newElement);
	}
	
	/**
	 * Returns additional template code for the form to add and edit entries.
	 * 
	 * @return	string
	 */
	public function getAdditionalTemplateCode() {
		return '';
	}
	
	/**
	 * Checks if the given string needs to be encapsuled by cdata and does so
	 * if required.
	 * 
	 * @param	string		$value
	 * @return	string
	 */
	protected function getAutoCdataValue($value) {
		if (strpos('<', $value) !== false || strpos('>', $value) !== false || strpos('&', $value) !== false) {
			$value = '<![CDATA[' . StringUtil::escapeCDATA($value) . ']]>';
		}
		
		return $value;
	}
	
	/**
	 * Returns the `import` element with the given identifier.
	 * 
	 * @param	XML	$xml
	 * @param	string	$identifier
	 * @return	\DOMElement|null
	 */
	protected function getElementByIdentifier(XML $xml, $identifier) {
		foreach ($this->getImportElements($xml->xpath()) as $element) {
			if ($this->getElementIdentifier($element) === $identifier) {
				return $element;
			}
		}
		
		return null;
	}
	
	/**
	 * Extracts the PIP object data from the given XML element.
	 *
	 * @param	\DOMElement	$element	element whose data is returned
	 * @param	bool		$saveData	is `true` if data is intended to be saved and otherwise `false`
	 * @return	array
	 */
	abstract protected function getElementData(\DOMElement $element, $saveData = false);
	
	/**
	 * Returns the identifier of the given `import` element.
	 * 
	 * @param	\DOMElement	$element
	 * @return	string
	 */
	abstract protected function getElementIdentifier(\DOMElement $element);
	
	/**
	 * Returns the xml code of an empty xml file with the appropriate structure
	 * present for a new entry to be added as if it was added to an existing
	 * file.
	 * 
	 * @return	string
	 */
	protected function getEmptyXml() {
		$xsdFilename = $this->getXsdFilename();
		
		return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data xmlns="http://www.woltlab.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.woltlab.com http://www.woltlab.com/XSD/vortex/{$xsdFilename}.xsd">
	<import></import>
</data>
XML;
	}
	
	/**
	 * Returns the name of the xsd file for this package installation plugin
	 * (without the file extension).
	 * 
	 * @return	string
	 */
	protected function getXsdFilename() {
		$classNamePieces = explode('\\', get_class($this));
		
		return lcfirst(str_replace('PackageInstallationPlugin', '', array_pop($classNamePieces)));
	}
	
	/**
	 * Returns a list of all pip entries of this pip.
	 * 
	 * @return	IDevtoolsPipEntryList
	 */
	public function getEntryList() {
		$xml = $this->getProjectXml();
		$xpath = $xml->xpath();
		
		$entryList = new DevtoolsPipEntryList();
		$this->setEntryListKeys($entryList);
		
		/** @var \DOMElement $element */
		foreach ($this->getImportElements($xpath) as $element) {
			$entryList->addEntry(
				$this->getElementIdentifier($element),
				array_intersect_key($this->getElementData($element), $entryList->getKeys())
			);
		}
		
		return $entryList;
	}
	
	/**
	 * Returns the list of available entry types. If only one entry type is
	 * available, this method returns an empty array.
	 * 
	 * For package installation plugins that support entries and categories
	 * for these entries, `['entries', 'categories']` should be returned.
	 * 
	 * @return	string[]
	 */
	public function getEntryTypes() {
		return [];
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getImportElements(\DOMXPath $xpath) {
		if ($this->entryType !== null) {
			if (substr($this->entryType, -3) === 'ies') {
				$objectTag = substr($this->entryType, 0, -3) . 'y';
			}
			else {
				$objectTag = substr($this->entryType, 0, -1);
			}
			
			return $xpath->query('/ns:data/ns:import/ns:' . $this->entryType . '/ns:' . $objectTag);
		}
		
		return parent::getImportElements($xpath);
	}
	
	/**
	 * Returns the xml object for this pip.
	 * 
	 * @return	XML
	 */
	protected function getProjectXml() {
		$fileLocation = $this->getXmlFileLocation();
		
		$xml = new XML();
		if (!file_exists($fileLocation)) {
			$xml->loadXML($fileLocation, $this->getEmptyXml());
		}
		else {
			$xml->load($fileLocation);
		}
		
		return $xml;
	}
	
	/**
	 * Returns the location of the xml file for this pip.
	 * 
	 * @return	string
	 */
	protected function getXmlFileLocation() {
		/** @var DevtoolsProject $project */
		$project = $this->installation->getProject();
		
		return $project->path . ($project->getPackage()->package === 'com.woltlab.wcf' ? 'com.woltlab.wcf/' : '') . static::getDefaultFilename();
	}
	
	/**
	 * Inserts the give new element into the given XML document.
	 * 
	 * @param	XML		$xml		XML document to which the element is added
	 * @param	\DOMElement	$newElement	added new element
	 */
	protected function insertNewXmlElement(XML $xml, \DOMElement $newElement) {
		$xml->xpath()->query('/ns:data/ns:import')->item(0)->appendChild($newElement);
	}
	
	/**
	 * Saves an object represented by an XML element in the database by either
	 * creating a new element (if `$oldElement = null`) or updating an existing
	 * element.
	 *
	 * @param	\DOMElement		$newElement	XML element with new data
	 * @param	\DOMElement|null	$oldElement	XML element with old data
	 */
	protected function saveObject(\DOMElement $newElement, \DOMElement $oldElement = null) {
		$newElementData = $this->getElementData($newElement, true);
		
		$existingRow = [];
		if ($oldElement !== null) {
			$sqlData = $this->findExistingItem($this->getElementData($oldElement, true));
			
			$statement = WCF::getDB()->prepareStatement($sqlData['sql']);
			$statement->execute($sqlData['parameters']);
			
			$existingRow = $statement->fetchArray();
		}
		
		$this->import($existingRow, $newElementData);
		
		$this->postImport();
		
		if (is_subclass_of($this->className, IEditableCachedObject::class)) {
			call_user_func([$this->className, 'resetCache']);
		}
	}
	
	/**
	 * Informs the pip of the identifier of the edited entry if the form to
	 * edit that entry has been submitted.
	 * 
	 * @param	string		$identifier
	 * 
	 * @throws	\InvalidArgumentException	if no such entry exists
	 */
	public function setEditedEntryIdentifier($identifier) {
		$this->editedEntry = $this->getElementByIdentifier($this->getProjectXml(), $identifier);
		
		if ($this->editedEntry === null) {
			throw new \InvalidArgumentException("Unknown entry with identifier '{$identifier}'.");
		}
	}
	
	/**
	 * Adds the data of the pip entry with the given identifier into the
	 * given form and returns `true`. If no entry with the given identifier
	 * exists, `false` is returned.
	 * 
	 * @param	string			$identifier
	 * @param	IFormDocument		$document
	 * @return	bool
	 */
	public function setEntryData($identifier, IFormDocument $document) {
		$xml = $this->getProjectXml();
		
		$element = $this->getElementByIdentifier($xml, $identifier);
		if ($element === null) {
			return false;
		}
		
		$data = $this->getElementData($element);
		
		/** @var IFormNode $node */
		foreach ($document->getIterator() as $node) {
			if ($node instanceof IFormField && $node->isAvailable()) {
				$key = $node->getId();
				
				if (isset($data[$key])) {
					$node->value($data[$key]);
				}
				else if ($node->getObjectProperty() !== $node->getId()) {
					$key = $node->getObjectProperty();
					
					try {
						if (isset($data[$key])) {
							$node->value($data[$key]);
						}
					}
					catch (\InvalidArgumentException $e) {
						// ignore invalid argument exceptions for fields with object property
						// as there might be multiple fields with the same object property but
						// different possible values (for example when using single selection
						// form fields to set the parent element)
					}
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Sets the keys of the given (empty) entry list.
	 *
	 * @param	IDevtoolsPipEntryList	$entryList
	 */
	abstract protected function setEntryListKeys(IDevtoolsPipEntryList $entryList);
	
	/**
	 * Sets the type of the currently handled pip entries.
	 * 
	 * @param	string		$entryType	currently handled pip entry type
	 * 
	 * @throws	\InvalidArgumentException	if the given entry type is invalid (see `getEntryTypes()` method)
	 */
	public function setEntryType($entryType) {
		if (!in_array($entryType, $this->getEntryTypes())) {
			throw new \InvalidArgumentException("Unknown entry type '{$entryType}'.");
		}
		
		$this->entryType = $entryType;
	}
}
