<?php
/**
 * UserPanel for Nette 2.0
 *
 * @author Mikuláš Dítě
 * @license MIT
 */

namespace Bazo\Extensions\UserPanel\Diagnostics;

use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette\Diagnostics\Debugger;
use Nette\Environment;
use Nette\Diagnostics\IBarPanel;
use Nette\Security\AuthenticationException;
use Nette\Latte\Engine;
use Nette\Security\IAuthenticator;
use Bazo\Extensions\UserPanel\Providers\CredentialsProvider;

class Panel extends Control implements IBarPanel
{

	/** @var \Nette\Http\User */
	private $user;

	/** @var array username => password */
	private $credentials = array();

	/** @var string */
	private $userColumn = 'username';

	/** @var \Nette\Application\UI\Presenter */
	private $presenter;

	/** @var CredentialsProvider */
	private $credentialsProvider;
	
	/** @var Authenticator */
	private $authenticator;

	/**
	 * @throws \LogicException
	 */
	public function __construct(CredentialsProvider $credentialsProvider, IAuthenticator $authenticator)
	{
		$this->presenter = $presenter = Environment::getApplication()->presenter;
		parent::__construct($presenter, 'UserPanel');
		if ($presenter === NULL) {
			throw new \LogicException('UserPanel must be registered in BasePresenter::startup(), not in bootstrap.');
		}
		
		$this->credentialsProvider = $credentialsProvider;
		$this->authenticator = $authenticator;
		$this->user = Environment::getUser();
	}



	/**
	 * Renders HTML code for custom tab
	 * IDebugPanel
	 * @return void
	 */
	public function getTab()
	{
		return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAnpJREFUeNqEU19IU1EY/927e52bWbaMQLbJwmgP0zIpffDFUClsyF56WJBQkv1RyJeo2IMPEghRQeAIoscegpBqTy6y3CDwrdzDwjCVkdqmzT+7u//O1jm3knkV/MF3z3e+8zu/7zv3O4crFotgaHC7jfHrwgKuBYPtVqt1BBx3SlNV5HK5KSmXu/N6fPxTKY+BMwvUNzY22cvFz6TIi0TXoWkaFEWBrkra+rrUtJLJTJcKCDCBZrqvyBaRCTMBnRCwKhRZFlVFuUspl0r5OwRUKXu+opxgsP8qfE4Bmk7wZV7Bg5FRqIR0m/m8OfA7K9n6bt1GvbeWlq2CKxCcPnEM1wf6sZknFXsKDF+c+dHgVKBmf4JoqmHMb/Va8OTK4vSeAhThpW9vwdsPociJ1ATD/zU7bqyZyVtdKMWHIXH0SJ3/RrWn05hn5t5jeeZN+OyQdtPMFbA77i1/f9dE7cy/+RS10G7EbRX4fL42OvQGAoFgT6uM2uPnjHhq9iNeTABjY2Mv6fR5IpGY2Cbg9XqPUr/PZrMNOJ1Oq65pfCQSwcPwK1TtE9F7OYCurgsQRbGQSqWUfD7/lPKfJZPJWc7j8ZzkeX7S5XLZHA6HIEkSqBCam5uxYqnDwf02WDeTiMVikGUZdrsdq6urOhWSCSGdFhoIud3ulrKyMiGbzRrXVqX9j8fj8Pu7UXO4EiPDIZYdNDN7F6DvhKf7+HQ6bRGoaju970bm/2CZmCXn0nAcyBn+xsbG1joTooJsbxv71LDNhUJh299lpPnFNaxt/hVjlZWCPTIar+YEQXhEzzxobk9HRyeWrC2oqhRRnplENBrd0UKa5PEfAQYAH6s95RSa3ooAAAAASUVORK5CYII=">' .
			($this->user->isLoggedIn() ? 'Logged as <span style="font-style: italic; margin: 0; padding: 0;">' . $this->getUsername() . '</span>' : 'Guest');
	}



	/**
	 * Renders HTML code for custom panel
	 * IDebugPanel
	 * @return void
	 */
	public function getPanel()
	{
		$template = parent::getTemplate();

		$data = $this->getData();

		$form = $this->getComponent('login');
		if ($this->user->isLoggedIn()) {
			$form['user']->setDefaultValue(strToLower($this->getUsername()));
		} else {
			$form['user']->setDefaultValue('__guest');
		}

		$template->setFile(__DIR__ . '/panel.latte');

		$template->registerFilter(new Engine());
		$template->user = $this->user;
		$template->data = $data;
		$template->userColumn = $this->userColumn;
		$template->username = $this->getUsername();
		return $template->__toString();
	}



	/**
	 * IDebugPanel
	 * @return string
	 */
	public function getId()
	{
		return __CLASS__;
	}



	/**
	 * Registers panel to Debug bar
	 * @return UserPanel;
	 */
	public static function register(CredentialsProvider $credentialsProvider, IAuthenticator $authenticator)
	{
		$panel = new self($credentialsProvider, $authenticator);
		Debugger::addPanel($panel);
		return $panel;
	}



	/**
	 * Username from user->identity->data from column set via setNameColumn()
	 * @return string|NULL
	 */
	public function getUsername()
	{
		$data = $this->getData();
		return isset($data[$this->userColumn]) ? $data[$this->userColumn] : NULL;
	}



	/**
	 * $user->identity->data
	 * @return array
	 */
	private function getData()
	{
		if (method_exists($this->user->identity, 'getData')) {
			return $this->user->identity->data;
		}
		return array();
	}



	/**
	 * @param string $username default value
	 * @param string $password default value
	 * @return UserPanel provides fluent interface
	 */
	public function addCredentials($username, $password)
	{
		$this->credentials[$username] = $password;
		return $this;
	}

	/**
	 * Sets which $user->identity->data column is supposed to be username
	 * @param string $column
	 * @return UserPanel provides fluent interface
	 */
	public function setNameColumn($column)
	{
		$this->userColumn = $column;
		return $this;
	}

	/**
	 * Returns value => name arrray for filling radio list and add __guest
	 * Original array in $this->credentials is not used as this prevents
	 * sending passwords to the browser
	 * @return array
	 */
	public function getCredentialsRadioData()
	{
		$data = array();
		foreach ($this->credentialsProvider as $username => $password) {
			$data[$username] = $username;
		}
		$data['__guest'] = 'guest';
		$this->credentials = $data;
		return $data;
	}



	/**
	 * Sign in form component factory.
	 * @return Nette\Application\AppForm
	 */
	public function createComponentLogin()
	{
		$form = new Form();

		$form->addRadioList('user', NULL, $this->getCredentialsRadioData())
			->setAttribute('class', 'onClickSubmit');

		$form->addSubmit('send', 'Log in');

		$form->onSuccess[] = callback($this, 'onLoginSubmitted');
		return $form;
	}



	/**
	 * @param Form $form
	 */
	public function onLoginSubmitted(Form $form)
	{
		try 
		{
			$values = $form->getValues();
			$username = $values['user'];
			if ($username == '__guest') 
			{
				$this->user->logout(TRUE);
			} 
			else 
			{
				$password = $this->credentials[$username];
				$this->user->setAuthenticator($this->authenticator);
				$this->user->login($username, $password);
			}

			$this->redirect('this');
		} 
		catch (AuthenticationException $e) 
		{
			$this->presenter->flashMessage($e->getMessage(), 'error');
			$this->redirect('this');
		}
	}
}
