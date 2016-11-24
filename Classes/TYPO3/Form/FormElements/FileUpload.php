<?php
namespace TYPO3\Form\FormElements;

/*
 * This file is part of the TYPO3.Form package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A generic file upload form element
 */
class FileUpload extends \TYPO3\Form\Core\Model\AbstractFormElement
{
    /**
     * @return void
     */
    public function initializeFormElement()
    {
        $this->setDataType(\Neos\Flow\ResourceManagement\PersistentResource::class);
        $fileTypeValidator = new \TYPO3\Form\Validation\FileTypeValidator(array('allowedExtensions' => $this->properties['allowedExtensions']));
        $this->addValidator($fileTypeValidator);
    }
}
