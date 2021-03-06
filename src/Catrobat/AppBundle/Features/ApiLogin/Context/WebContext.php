<?php
namespace Catrobat\AppBundle\Features\ApiLogin\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Catrobat\AppBundle\Features\Helpers\BaseContext;
use Behat\Gherkin\Node\TableNode;

class WebContext extends BaseContext
{
    private $username;

    private $token;

    /**
     * @BeforeScenario
     */
    public function followRedirects()
    {
        $this->getClient()->followRedirects(true);
    }

    /**
     * @Given /^I have a valid upload token$/
     */
    public function iHaveAValidUploadToken()
    {
        $this->token = 'VALIDTOKEN';
        $this->username = 'TokenUser';
        $this->insertUser(array(
            'name' => $this->username,
            'token' => $this->token
        ));
    }

    /**
     * @When /^I login with this token and my username$/
     */
    public function iLoginWithThisTokenAndMyUsername()
    {
        $uri = '/pocketcode/tokenlogin';
        $parameters = array(
            'username' => $this->username,
            'token' => $this->token
        );
        $this->getClient()->request('GET', $uri . '?' . http_build_query($parameters));
        assertEquals(200, $this->getClient()
            ->getResponse()
            ->getStatusCode(), $this->getClient()->getResponse());
    }

    /**
     * @Then /^I should be logged in$/
     */
    public function iShouldBeLoggedIn()
    {
        assertEquals(0, $this->getClient()->getCrawler()->filter('#btn-login')->count());
        assertContains('TokenUser', $this->getClient()
            ->getResponse()
            ->getContent());
    }

    /**
     * @Given /^I am logged in$/
     */
    public function iAmLoggedIn()
    {
        $this->iHaveAValidUploadToken();
        $this->iLoginWithThisTokenAndMyUsername();
    }

    /**
     * @Given /^I have an invalid upload token$/
     */
    public function iHaveAnInvalidUploadToken()
    {
        $this->token = "INVALID";
    }

    /**
     * @Then /^I should be logged out$/
     */
    public function iShouldBeLoggedOut()
    {
        assertGreaterThanOrEqual(1, $this->getClient()->getCrawler()->filter('#btn-login')->count());
        assertNotContains('TokenUser', $this->getClient()
            ->getResponse()
            ->getContent());
        
    }
    
    /**
     * @Given /^I there is a user with$/
     */
    public function iThereIsAUserWith(TableNode $table)
    {
        $values = $table->getRowsHash();
        $this->insertUser(array(
            'name' => $values['username'],
            'token' => $values['token']
        ));
    }
    
    /**
     * @Given /^I have a HTTP Request:$/
     */
    public function iHaveAHttpRequest(TableNode $table)
    {
        $values = $table->getRowsHash();
        $this->method = $values['Method'];
        $this->url = $values['Url'];
    }
    
    /**
     * @Given /^I use the GET parameters:$/
     */
    public function iUseTheGetParameters(TableNode $table)
    {
        $values = $table->getRowsHash();
        $this->get_parameters = $values;
    }
    
    /**
     * @When /^I invoke the Request$/
     */
    public function iInvokeTheRequest()
    {
        $this->getClient()->request('GET', $this->url . '?' . http_build_query($this->get_parameters), array(), array(), array());
    }
    
    /**
     * @Then /^I should be on "([^"]*)"$/
     */
    public function iShouldBeOn($arg1)
    {
        $path = $this->getClient()->getRequest()->getPathInfo() . "?" . $this->getClient()->getRequest()->getQueryString(); 
        assertEquals($arg1, $path);
    }
    
}
