<?php
namespace Neos\Form\Tests\Unit\Core\Model;

/*
 * This file is part of the Neos.Form package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Validator\ConjunctionValidator;
use Neos\Flow\Validation\Validator\NotEmptyValidator;
use Neos\Flow\Validation\Validator\StringLengthValidator;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Form\Core\Model\AbstractFormElement;
use Neos\Form\Core\Model\FormDefinition;
use Neos\Form\Core\Model\Page;
use Neos\Form\Core\Model\ProcessingRule;
use Neos\Form\Factory\ArrayFormFactory;
use Neos\Form\FormElements\GenericFormElement;
use Neos\Form\FormElements\Section;

/**
 * Test for Page Domain Model
 * @covers \Neos\Form\Core\Model\Page<extended>
 * @covers \Neos\Form\Core\Model\AbstractFormElement<extended>
 */
class PageTest extends UnitTestCase
{
    /**
     * @test
     */
    public function identifierSetInConstructorCanBeReadAgain()
    {
        $page = new Page('foo');
        $this->assertSame('foo', $page->getIdentifier());

        $page = new Page('bar');
        $this->assertSame('bar', $page->getIdentifier());
    }

    /**
     * @test
     */
    public function defaultTypeIsCorrect()
    {
        $page = new Page('foo');
        $this->assertSame('Neos.Form:Page', $page->getType());
    }

    /**
     * @test
     */
    public function typeCanBeOverridden()
    {
        $page = new Page('foo', 'Neos.Foo:Bar');
        $this->assertSame('Neos.Foo:Bar', $page->getType());
    }

    public function invalidIdentifiers()
    {
        return array(
            'Null Identifier' => array(null),
            'Integer Identifier' => array(42),
            'Empty String Identifier' => array('')
        );
    }

    /**
     * @test
     * @expectedException \Neos\Form\Exception\IdentifierNotValidException
     * @dataProvider invalidIdentifiers
     * @param mixed $identifier
     */
    public function ifBogusIdentifierSetInConstructorAnExceptionIsThrown($identifier)
    {
        new Page($identifier);
    }

    /**
     * @test
     */
    public function getElementsReturnsEmptyArrayByDefault()
    {
        $page = new Page('foo');
        $this->assertSame([], $page->getElements());
    }

    /**
     * @test
     */
    public function getElementsRecursivelyReturnsEmptyArrayByDefault()
    {
        $page = new Page('foo');
        $this->assertSame([], $page->getElementsRecursively());
    }

    /**
     * @test
     */
    public function getElementsRecursivelyReturnsFirstLevelFormElements()
    {
        $page = new Page('foo');
        /** @var AbstractFormElement|\PHPUnit_Framework_MockObject_MockObject $element1 */
        $element1 = $this->getMockBuilder(AbstractFormElement::class)->setMethods(array('dummy'))->disableOriginalConstructor()->getMock();
        /** @var AbstractFormElement|\PHPUnit_Framework_MockObject_MockObject $element2 */
        $element2 = $this->getMockBuilder(AbstractFormElement::class)->setMethods(array('dummy'))->disableOriginalConstructor()->getMock();
        $page->addElement($element1);
        $page->addElement($element2);
        $this->assertSame(array($element1, $element2), $page->getElementsRecursively());
    }

    /**
     * @test
     */
    public function getElementsRecursivelyReturnsRecursiveFormElementsInCorrectOrder()
    {
        $page = new Page('foo');

        /** @var AbstractFormElement|\PHPUnit_Framework_MockObject_MockObject $element1 */
        $element1 = $this->getMockBuilder(AbstractFormElement::class)->setMethods(array('dummy'))->disableOriginalConstructor()->getMock();
        /** @var Section|\PHPUnit_Framework_MockObject_MockObject $element2 */
        $element2 = $this->getMockBuilder(Section::class)->setMethods(array('dummy'))->disableOriginalConstructor()->getMock();
        /** @var AbstractFormElement|\PHPUnit_Framework_MockObject_MockObject $element21 */
        $element21 = $this->getMockBuilder(AbstractFormElement::class)->setMethods(array('dummy'))->disableOriginalConstructor()->getMock();
        /** @var AbstractFormElement|\PHPUnit_Framework_MockObject_MockObject $element22 */
        $element22 = $this->getMockBuilder(AbstractFormElement::class)->setMethods(array('dummy'))->disableOriginalConstructor()->getMock();
        $element2->addElement($element21);
        $element2->addElement($element22);
        /** @var AbstractFormElement|\PHPUnit_Framework_MockObject_MockObject $element3 */
        $element3 = $this->getMockBuilder(AbstractFormElement::class)->setMethods(array('dummy'))->disableOriginalConstructor()->getMock();

        $page->addElement($element1);
        $page->addElement($element2);
        $page->addElement($element3);
        $this->assertSame(array($element1, $element2, $element21, $element22, $element3), $page->getElementsRecursively());
    }

    /**
     * @test
     * @expectedException \Neos\Form\Exception\FormDefinitionConsistencyException
     */
    public function aFormElementCanOnlyBeAttachedToASinglePage()
    {
        /** @var AbstractFormElement|\PHPUnit_Framework_MockObject_MockObject $element */
        $element = $this->getMockBuilder(AbstractFormElement::class)->setMethods(array('dummy'))->disableOriginalConstructor()->getMock();

        $page1 = new Page('bar1');
        $page2 = new Page('bar2');

        $page1->addElement($element);
        $page2->addElement($element);
    }

    /**
     * @test
     */
    public function addElementAddsElementAndSetsBackReferenceToPage()
    {
        $page = new Page('bar');
        /** @var AbstractFormElement|\PHPUnit_Framework_MockObject_MockObject $element */
        $element = $this->getMockBuilder(AbstractFormElement::class)->setMethods(array('dummy'))->disableOriginalConstructor()->getMock();
        $page->addElement($element);
        $this->assertSame(array($element), $page->getElements());
        $this->assertSame($page, $element->getParentRenderable());
    }

    /**
     * @test
     */
    public function createElementCreatesElementAndAddsItToForm()
    {
        $formDefinition = $this->getDummyFormDefinition();
        $page = $formDefinition->createPage('myPage');
        $element = $page->createElement('myElement', 'Neos.Form:MyElementType');

        $this->assertSame('myElement', $element->getIdentifier());
        $this->assertInstanceOf(GenericFormElement::class, $element);
        $this->assertSame('Neos.Form:MyElementType', $element->getType());
        $this->assertSame(array($element), $page->getElements());
    }

    /**
     * @test
     */
    public function createElementSetsAdditionalPropertiesInElement()
    {
        $formDefinition = $this->getDummyFormDefinition();
        $page = $formDefinition->createPage('myPage');
        $element = $page->createElement('myElement', 'Neos.Form:MyElementTypeWithAdditionalProperties');

        $this->assertSame('my label', $element->getLabel());
        $this->assertSame('This is the default value', $element->getDefaultValue());
        $this->assertSame(array('property1' => 'val1', 'property2' => 'val2'), $element->getProperties());
        $this->assertSame(array('ro1' => 'rv1', 'ro2' => 'rv2'), $element->getRenderingOptions());
        $this->assertSame('MyRendererClassName', $element->getRendererClassName());
    }

    /**
     * @test
     * @expectedException \Neos\Form\Exception\FormDefinitionConsistencyException
     */
    public function createElementThrowsExceptionIfPageIsNotAttachedToParentForm()
    {
        $page = new Page('id');
        $page->createElement('myElement', 'Neos.Form:MyElementType');
    }

    /**
     * @test
     * @expectedException \Neos\Form\Exception\TypeDefinitionNotFoundException
     */
    public function createElementThrowsExceptionIfImplementationClassNameNotFound()
    {
        $formDefinition = $this->getDummyFormDefinition();
        $page = $formDefinition->createPage('myPage');
        $page->createElement('myElement', 'Neos.Form:MyElementTypeWithoutImplementationClassName');
    }

    /**
     * @test
     * @expectedException \Neos\Form\Exception\TypeDefinitionNotValidException
     */
    public function createElementThrowsExceptionIfImplementationClassNameDoesNotImplementFormElementInterface()
    {
        $formDefinition = $this->getDummyFormDefinition();
        $page = $formDefinition->createPage('myPage');
        $page->createElement('myElement', 'Neos.Form:MyElementTypeWhichDoesNotImplementFormElementInterface');
    }

    /**
     * @test
     * @expectedException \Neos\Form\Exception\TypeDefinitionNotValidException
     */
    public function createElementThrowsExceptionIfUnknownPropertyFoundInTypeDefinition()
    {
        $formDefinition = $this->getDummyFormDefinition();
        $page = $formDefinition->createPage('myPage');
        $page->createElement('myElement', 'Neos.Form:MyElementTypeWithUnknownProperties');
    }

    /**
     * @test
     */
    public function moveElementBeforeMovesElementBeforeReferenceElement()
    {
        $formDefinition = $this->getDummyFormDefinition();
        $page = $formDefinition->createPage('myPage');
        $element1 = $page->createElement('myElement', 'Neos.Form:MyElementType');
        $element2 = $page->createElement('myElement2', 'Neos.Form:MyElementType');

        $this->assertSame(array($element1, $element2), $page->getElements());
        $page->moveElementBefore($element2, $element1);
        $this->assertSame(array($element2, $element1), $page->getElements());
    }

    /**
     * @test
     * @expectedException \Neos\Form\Exception\FormDefinitionConsistencyException
     */
    public function moveElementBeforeThrowsExceptionIfElementsAreNotOnSamePage()
    {
        $formDefinition = $this->getDummyFormDefinition();
        $page1 = $formDefinition->createPage('myPage1');
        $page2 = $formDefinition->createPage('myPage2');

        $element1 = $page1->createElement('myElement', 'Neos.Form:MyElementType');
        $element2 = $page2->createElement('myElement2', 'Neos.Form:MyElementType');

        $page1->moveElementBefore($element1, $element2);
    }

    /**
     * @test
     */
    public function moveElementAfterMovesElementAfterReferenceElement()
    {
        $formDefinition = $this->getDummyFormDefinition();
        $page = $formDefinition->createPage('myPage');
        $element1 = $page->createElement('myElement', 'Neos.Form:MyElementType');
        $element2 = $page->createElement('myElement2', 'Neos.Form:MyElementType');

        $this->assertSame(array($element1, $element2), $page->getElements());
        $page->moveElementAfter($element1, $element2);
        $this->assertSame(array($element2, $element1), $page->getElements());
    }

    /**
     * @test
     * @expectedException \Neos\Form\Exception\FormDefinitionConsistencyException
     */
    public function moveElementAfterThrowsExceptionIfElementsAreNotOnSamePage()
    {
        $formDefinition = $this->getDummyFormDefinition();
        $page1 = $formDefinition->createPage('myPage1');
        $page2 = $formDefinition->createPage('myPage2');

        $element1 = $page1->createElement('myElement', 'Neos.Form:MyElementType');
        $element2 = $page2->createElement('myElement2', 'Neos.Form:MyElementType');

        $page1->moveElementAfter($element1, $element2);
    }

    /**
     * @test
     */
    public function removeElementRemovesElementFromCurrentPageAndUnregistersItFromForm()
    {
        $formDefinition = $this->getDummyFormDefinition();
        $page1 = $formDefinition->createPage('myPage1');
        /** @var AbstractFormElement|\PHPUnit_Framework_MockObject_MockObject $element1 */
        $element1 = $page1->createElement('myElement', 'Neos.Form:MyElementType');

        $page1->removeElement($element1);

        $this->assertSame([], $page1->getElements());
        $this->assertNull($formDefinition->getElementByIdentifier('myElement'));

        $this->assertNull($element1->getParentRenderable());
    }

    /**
     * @test
     * @expectedException \Neos\Form\Exception\FormDefinitionConsistencyException
     */
    public function removeElementThrowsExceptionIfElementIsNotOnCurrentPage()
    {
        $formDefinition = $this->getDummyFormDefinition();
        $page1 = $formDefinition->createPage('myPage1');
        /** @var AbstractFormElement|\PHPUnit_Framework_MockObject_MockObject $element1 */
        $element1 = $this->getMockBuilder(AbstractFormElement::class)->setMethods(array('dummy'))->disableOriginalConstructor()->getMock();

        $page1->removeElement($element1);
    }

    /**
     * @test
     */
    public function validatorKeyCorrectlyAddsValidator()
    {
        $formDefinition = $this->getDummyFormDefinition();

        $mockProcessingRule = $this->getAccessibleMock(ProcessingRule::class, array('dummy'));
        /** @noinspection PhpUndefinedMethodInspection */
        $mockProcessingRule->_set('validator', new ConjunctionValidator());
        $formDefinition->expects($this->any())->method('getProcessingRule')->with('asdf')->will($this->returnValue($mockProcessingRule));

        $page1 = $formDefinition->createPage('myPage1');
        /** @var AbstractFormElement|\PHPUnit_Framework_MockObject_MockObject $element */
        $element = $page1->createElement('asdf', 'Neos.Form:MyElementWithValidator');
        $this->assertTrue($element->isRequired());
        $validators = $element->getValidators();
        $validators = iterator_to_array($validators);
        /** @var ValidatorInterface $firstValidator */
        $firstValidator = $validators[0];
        $this->assertSame(2, count($validators));
        $this->assertInstanceOf(StringLengthValidator::class, $firstValidator);
        $this->assertSame(array('minimum' => 10, 'maximum' => PHP_INT_MAX), $firstValidator->getOptions());
    }

    /**
     * @test
     * @expectedException \Neos\Form\Exception\ValidatorPresetNotFoundException
     */
    public function validatorKeyThrowsExceptionIfValidatorPresetIsNotFound()
    {
        $formDefinition = $this->getDummyFormDefinition();

        $page1 = $formDefinition->createPage('myPage1');
        $page1->createElement('asdf', 'Neos.Form:MyElementWithBrokenValidator');
    }

    /**
     * @return FormDefinition|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDummyFormDefinition()
    {
        $formDefinitionConstructorArguments = array('myForm', array(
            'validatorPresets' => array(
                'MyValidatorIdentifier' => array(
                    'implementationClassName' => StringLengthValidator::class
                ),
                'MyOtherValidatorIdentifier' => array(
                    'implementationClassName' => NotEmptyValidator::class
                ),
            ),
            'formElementTypes' => array(
                'Neos.Form:Form' => [],
                'Neos.Form:Page' => array(
                    'implementationClassName' => Page::class
                ),
                'Neos.Form:MyElementType' => array(
                    'implementationClassName' => GenericFormElement::class
                ),
                'Neos.Form:MyElementTypeWithAdditionalProperties' => array(
                    'implementationClassName' => GenericFormElement::class,
                    'label' => 'my label',
                    'defaultValue' => 'This is the default value',
                    'properties' => array(
                        'property1' => 'val1',
                        'property2' => 'val2'
                    ),
                    'renderingOptions' => array(
                        'ro1' => 'rv1',
                        'ro2' => 'rv2'
                    ),
                    'rendererClassName' => 'MyRendererClassName'
                ),
                'Neos.Form:MyElementTypeWithoutImplementationClassName' => [],
                'Neos.Form:MyElementTypeWithUnknownProperties' => array(
                    'implementationClassName' => GenericFormElement::class,
                    'unknownProperty' => 'foo'
                ),
                'Neos.Form:MyElementTypeWhichDoesNotImplementFormElementInterface' => array(
                    'implementationClassName' => ArrayFormFactory::class,
                ),
                'Neos.Form:MyElementWithValidator' => array(
                    'implementationClassName' => GenericFormElement::class,
                    'validators' => array(
                        array(
                            'identifier' => 'MyValidatorIdentifier',
                            'options' => array('minimum' => 10)
                        ),
                        array(
                            'identifier' => 'MyOtherValidatorIdentifier'
                        ),
                    )
                ),
                'Neos.Form:MyElementWithBrokenValidator' => array(
                    'implementationClassName' => GenericFormElement::class,
                    'validators' => array(
                        array(
                            'identifier' => 'nonExisting',
                        )
                    )
                )

            )
        ));

        $formDefinition = $this->getMockBuilder(FormDefinition::class)->setMethods(array('getProcessingRule'))->setConstructorArgs($formDefinitionConstructorArguments)->getMock();
        return $formDefinition;
    }
}
