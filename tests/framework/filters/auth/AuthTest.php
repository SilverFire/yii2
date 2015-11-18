<?php

namespace yiiunit\framework\filters\auth;

use Yii;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yiiunit\framework\filters\stubs\UserIdentity;

/**
 * @group filters
 * @author Dmitry Naumenko <d.naumenko.a@gmail.com>
 * @since 2.0.7
 */
class AuthTest extends \yiiunit\TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $_SERVER['SCRIPT_FILENAME'] = "/index.php";
        $_SERVER['SCRIPT_NAME'] = "/index.php";

        $appConfig = [
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::className()
                ],
            ],
            'controllerMap' => [
                'test-auth' => TestAuthController::className()
            ]
        ];

        $this->mockWebApplication($appConfig);
    }

    public function tokenProvider()
    {
        return [
            ['token1', 'user1'],
            ['token2', 'user2'],
            ['token3', 'user3'],
            ['unknown', null],
            [null, null],
        ];
    }

    public function authOnly($token, $login, $filter, $action)
    {
        /** @var TestAuthController $controller */
        $controller = Yii::$app->createController('test-auth')[0];
        $controller->authenticatorConfig = ArrayHelper::merge($filter, ['only' => [$action]]);
        if ($login === null) {
            $this->setExpectedException('\yii\web\UnauthorizedHttpException');
        }
        $this->assertEquals($login, $controller->run($action));
    }

    public function authOptional($token, $login, $filter, $action)
    {
        /** @var TestAuthController $controller */
        $controller = Yii::$app->createController('test-auth')[0];
        $controller->authenticatorConfig = ArrayHelper::merge($filter, ['optional' => [$action]]);
        $this->assertEquals($login, $controller->run($action));
    }

    public function authExcept($token, $login, $filter, $action)
    {
        /** @var TestAuthController $controller */
        $controller = Yii::$app->createController('test-auth')[0];
        $controller->authenticatorConfig = ArrayHelper::merge($filter, ['except' => ['other']]);
        if ($login === null) {
            $this->setExpectedException('\yii\web\UnauthorizedHttpException');
        }
        $this->assertEquals($login, $controller->run($action));
    }

    /**
     * @dataProvider tokenProvider
     */
    public function testQueryParamAuth($token, $login) {
        $_GET['access-token'] = $token;
        $filter = ['class' => QueryParamAuth::className()];
        $this->authOnly($token, $login, $filter, 'query-param-auth');
        $this->authOptional($token, $login, $filter, 'query-param-auth');
        $this->authExcept($token, $login, $filter, 'query-param-auth');
    }

    /**
     * @dataProvider tokenProvider
     */
    public function testHttpBasicAuth($token, $login) {
        $_SERVER['PHP_AUTH_USER'] = $token;
        $_SERVER['PHP_AUTH_PW'] = 'whatever, we are testers';
        $filter = ['class' => HttpBasicAuth::className()];
        $this->authOnly($token, $login, $filter, 'basic-auth');
        $this->authOptional($token, $login, $filter, 'basic-auth');
        $this->authExcept($token, $login, $filter, 'basic-auth');
    }

    /**
     * @dataProvider tokenProvider
     */
    public function testHttpBasicAuthCustom($token, $login) {
        $_SERVER['PHP_AUTH_USER'] = $login;
        $_SERVER['PHP_AUTH_PW'] = 'whatever, we are testers';
        $filter = [
            'class' => HttpBasicAuth::className(),
            'auth' => function ($username, $password) {
                if (preg_match('/\d$/', $username)) {
                    return UserIdentity::findIdentity($username);
                }

                return null;
            }
        ];
        $this->authOnly($token, $login, $filter, 'basic-auth');
        $this->authOptional($token, $login, $filter, 'basic-auth');
        $this->authExcept($token, $login, $filter, 'basic-auth');
    }

    /**
     * @dataProvider tokenProvider
     */
    public function testHttpBearerAuth($token, $login) {
        Yii::$app->request->headers->set('Authorization', "Bearer $token");
        $filter = ['class' => HttpBearerAuth::className()];
        $this->authOnly($token, $login, $filter, 'bearer-auth');
        $this->authOptional($token, $login, $filter, 'bearer-auth');
        $this->authExcept($token, $login, $filter, 'bearer-auth');
    }
}

/**
 * Class TestAuthController
 *
 * @author Dmitry Naumenko <d.naumenko.a@gmail.com>
 * @since 2.0.7
 */
class TestAuthController extends Controller
{
    public $authenticatorConfig = [];

    public function behaviors()
    {
        return ['authenticator' => $this->authenticatorConfig];
    }

    public function actionBasicAuth()
    {
        return Yii::$app->user->id;
    }

    public function actionBearerAuth()
    {
        return Yii::$app->user->id;
    }

    public function actionQueryParamAuth()
    {
        return Yii::$app->user->id;
    }
}