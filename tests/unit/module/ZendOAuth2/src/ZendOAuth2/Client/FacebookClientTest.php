<?php

namespace ZendOAuth2\Client;

use PHPUnit_Framework_TestCase;
use Zend\Mvc\Application;

class FacebookClientTest extends PHPUnit_Framework_TestCase
{
    
    protected $providerName = 'Facebook';
    
    public function setup()
    {
         $this->client = $this->getClient();
    }
    
    public function tearDown()
    {

        unset($this->client->getSessionContainer()->token);
        unset($this->client->getSessionContainer()->state);
        unset($this->client->getSessionContainer()->info);
        
    }
    
    public function getClient()
    {
        $me = new \ZendOAuth2\Client\Facebook;
        
        $cf = array(
            'zendoauth2' => array(
                'facebook' => array(
                    'scope' => array(
                        /*
                        'user_about_me',
                        'user_activities',
                        'user_birthday',
                        'read_friendlists',
                        //'...'
                        */
                     ),
                    'auth_uri'      => 'https://www.facebook.com/dialog/oauth',
                    'token_uri'     => 'https://graph.facebook.com/oauth/access_token',
                    'info_uri'      => 'https://graph.facebook.com/me',
                    'client_id'     => 'your id',
                    'client_secret' => 'your secret',
                    'redirect_uri'  => 'your callback url which links to your controller',
                )
            )
        );

        $bootstrap = Application::init(include 'tests/unit/test.config.php');
  
        $me->setOptions(new \ZendOAuth2\ClientOptions($cf['zendoauth2']['facebook']));
        return $me;
    }
    
    public function testInstanceTypes()
    {
        $this->assertInstanceOf('ZendOAuth2\AbstractOAuth2Client', $this->client);
        $this->assertInstanceOf('ZendOAuth2\Client\\'.$this->providerName, $this->client);
        $this->assertInstanceOf('ZendOAuth2\ClientOptions', $this->client->getOptions());
        $this->assertInstanceOf('Zend\Session\Container', $this->client->getSessionContainer());
        $this->assertInstanceOf('ZendOAuth2\OAuth2HttpClient', $this->client->getHttpClient());
    }
    
    public function testGetProviderName()
    {
        $this->assertSame(strtolower($this->providerName), $this->client->getProvider());
    }
    
    public function testSetHttpClient()
    {
        $httpClientMock = $this->getMock(
                '\ZendOAuth2\OAuth2HttpClient',
                null,
                array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $this->client->setHttpClient($httpClientMock);
    }
    
    public function testFailSetHttpClient()
    {
        $this->setExpectedException('ZendOAuth2\Exception\HttpClientException');
        $this->client->setHttpClient(new \Zend\Http\Client);
    }
    
    public function testSessionState()
    {
        
        $this->assertEmpty($this->client->getState());
        $this->client->getUrl();
        $this->assertEquals(strlen($this->client->getState()), 32);
        
    }
    
    public function testLoginUrlCreation()
    {
        
        $uri = \Zend\Uri\UriFactory::factory($this->client->getUrl());
        $this->assertTrue($uri->isValid());
        
    }
    
    public function testGetScope()
    {
        
        if(count($this->client->getOptions()->getScope()) > 0) {
            $this->assertTrue(strlen($this->client->getScope()) > 0);
        } else {
            $this->assertTrue(strlen($this->client->getScope()) == 0);
            $this->client->getOptions()->setScope(array('some', 'scope'));
            $this->assertTrue(strlen($this->client->getScope()) > 0);
        }
        
    }
    
    public function testFailGetToken()
    {
        
        $this->client->getUrl();
        
        $request = new \Zend\Http\PhpEnvironment\Request;
        
        $this->assertFalse($this->client->getToken($request));
        
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => 'some state')));      
        
        $this->assertFalse($this->client->getToken($request));
        $error = $this->client->getError();
        $this->assertStringEndsWith('variables do not match the session variables.', $error['internal-error']);
        
        
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
        $this->assertFalse($this->client->getToken($request));
        $error = $this->client->getError();
        $this->assertStringEndsWith('settings error.', $error['internal-error']);
        
    }
    
    public function testFailGetTokenMocked()
    {
        
        $this->client->getUrl();
        
        $httpClientMock = $this->getMock(
            '\ZendOAuth2\OAuth2HttpClient',
            array('send'),
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $httpClientMock->expects($this->any())
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getFaultyMockedTokenResponse')));
        
        $this->client->setHttpClient($httpClientMock);
        
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
        
        $this->assertFalse($this->client->getToken($request));
                
        $error = $this->client->getError();
        $this->assertStringEndsWith('Unknown error.', $error['internal-error']);
        
    }
    
    public function testGetTokenMocked()
    {
        
        $this->client->getUrl();
        
        $httpClientMock = $this->getMock(
            '\ZendOAuth2\OAuth2HttpClient',
            array('send'),
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $httpClientMock->expects($this->exactly(1))
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedTokenResponse')));
        
        $this->client->setHttpClient($httpClientMock);
        
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
        
        $this->assertTrue($this->client->getToken($request));
        
        $this->assertTrue($this->client->getToken($request)); // from session
        
        $this->assertTrue(strlen($this->client->getSessionToken()->access_token) > 0);
        
    }
    
    public function testGetInfo()
    {

        $httpClientMock = $this->getMock(
            '\ZendOAuth2\OAuth2HttpClient',
            array('send'),
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $httpClientMock->expects($this->exactly(1))
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedInfoResponse')));
        
        $this->client->setHttpClient($httpClientMock);
        
        $token = new \stdClass; // fake the session token exists
        $token->access_token = 'some';
        $this->client->getSessionContainer()->token = $token;
        
        $rs = $this->client->getInfo();
        $this->assertSame('500', $rs->id);
        
        $rs = $this->client->getInfo(); // from session
        $this->assertSame('500', $rs->id);
        
    }
    
    public function testFailNoReturnGetInfo()
    {
    
        $httpClientMock = $this->getMock(
                '\ZendOAuth2\OAuth2HttpClient',
                array('send'),
                array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
    
        $httpClientMock->expects($this->any())
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedInfoResponseEmpty')));
    
    
        $token = new \stdClass; // fake the session token exists
        $token->access_token = 'some';
        $this->client->getSessionContainer()->token = $token;
    
        $this->client->setHttpClient($httpClientMock);
    
        $this->assertFalse($this->client->getInfo());
    
        $error = $this->client->getError();
        $this->assertSame('Get info return value is empty.', $error['internal-error']);
    
    }
    
    public function testFailNoTokenGetInfo()
    {
    
        $httpClientMock = $this->getMock(
                '\ZendOAuth2\OAuth2HttpClient',
                array('send'),
                array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
    
        $httpClientMock->expects($this->any())
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedInfoResponse')));
    
        $this->client->setHttpClient($httpClientMock);
    
        $this->assertFalse($this->client->getInfo());
    
        $error = $this->client->getError();
        $this->assertSame('Session access token not found.', $error['internal-error']);
    
    }
    
    public function getMockedTokenResponse()
    {

        $response = new \Zend\Http\Response;

        $response->setContent('access_token=AAAEDkf9KDoQBABLbgWLQpyMUxZBQjkrCZC4Fw3C6EJWTeF7zZB0ymBTdPejD4gae08AZDZD&expires=5117581');

        return $response;

    }
    
    public function getFaultyMockedTokenResponse()
    {

        $response = new \Zend\Http\Response;

        $response->setContent('token=AAAEDkf9KDoQBABLCZC4Fw3C6EJWTeF7zZB0ymBTdPejD4gae08AZDZg&expires=1');

        return $response;

    }
    
    public function getMockedInfoResponse()
    {
    
        $content = '{
            "id": "500",
            "name": "John Doe",
            "first_name": "John",
            "last_name": "Doe",
            "link": "http:\/\/www.facebook.com\/john.doe",
            "username": "john.doe",
            "gender": "male",
            "timezone": 1,
            "locale": "sl_SI",
            "verified": true,
            "updated_time": "2012-09-14T12:37:27+0000"
        }';
    
        $response = new \Zend\Http\Response;
    
        $response->setContent($content);
    
        return $response;
    
    }
    
    public function getMockedInfoResponseEmpty()
    {
        
        $response = new \Zend\Http\Response;    
        return $response;
    
    }
    
}