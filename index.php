<?php


require './pdos/DatabasePdo.php';
require './pdos/IndexPdo.php';
require './pdos/JWTPdo.php';
require './vendor/autoload.php';
require './pdos/ProductPdo.php';
require './pdos/TownLifePdo.php';



use \Monolog\Logger as Logger;
use Monolog\Handler\StreamHandler;

date_default_timezone_set('Asia/Seoul');
ini_set('default_charset', 'utf8mb4');

//에러출력하게 하는 코드
//error_reporting(E_ALL); ini_set("display_errors", 1);

//Main Server API
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {

    /* ********************************** ProductController ***************************************** */
    //$r->addRoute('GET', '/product-test', ['TestController', 'getProduct']);
    //$r->addRoute('POST', '/product/interest', ['ProductController', 'interestProduct']);
    // 중고거래 물품 조회
    $r->addRoute('GET', '/product', ['ProductController', 'getProduct']);
    // 중고거래 상세정보 조회
    $r->addRoute('GET', '/product/{productIdx}', ['ProductController', 'getProductDetail']);
    // 중고거래 물품 등록
    $r->addRoute('POST', '/product', ['ProductController', 'createProduct']);
    //중고거래 물품 수정
    $r->addRoute('PATCH', '/product/{productIdx}', ['ProductController', 'changeProduct']);
    // 물품 끌어올리기
    $r->addRoute('PATCH', '/product/{productIdx}/up', ['ProductController', 'upProduct']);
    // 관심목록 추가
    $r->addRoute('POST', '/product/interest', ['ProductController', 'interestProduct']);
    // 물품 삭제
    $r->addRoute('DELETE', '/product/{productIdx}', ['ProductController', 'deleteProduct']);

    /* ********************************** TownLifeController ***************************************** */
    // 글 조회
    $r->addRoute('GET', '/content', ['TownLifeController', 'getContent']);
    // 글 세부내용 조회
    $r->addRoute('GET', '/content/{contentIdx}', ['TownLifeController', 'getContentDetail']);
    // 글 등록
    $r->addRoute('POST', '/content', ['TownLifeController', 'createContent']);
    // 글 수정
    $r->addRoute('PATCH', '/content/{contentIdx}', ['TownLifeController', 'changeContent']);
    // 글 공감
    $r->addRoute('POST', '/content/sympathy', ['TownLifeController', 'clickContentSympathy']);
    // 글 공감 유저 조회
    $r->addRoute('GET', '/content/{contentIdx}/sympathy', ['TownLifeController', 'getContentSympathy']);
    // 댓글 공감
    $r->addRoute('POST', '/comment/sympathy', ['TownLifeController', 'clickCommentSympathy']);
    // 댓글 공감 유저 조회
    $r->addRoute('GET', '/comment/{commentIdx}/sympathy', ['TownLifeController', 'getCommentSympathy']);
    // 댓글 등록
    $r->addRoute('POST', '/content/{contentIdx}/comment', ['TownLifeController', 'createComment']);
    // 대댓글 등록
    $r->addRoute('POST', '/content/{contentIdx}/comment/{commentIdx}/comment', ['TownLifeController', 'createCommentComment']);
    // 댓글 삭제
    $r->addRoute('DELETE', '/comment/{commentIdx}', ['TownLifeController', 'deleteComment']);
    // 글 삭제
    $r->addRoute('DELETE', '/content/{contentIdx}', ['TownLifeController', 'deleteContent']);

    /* ******************   JWT   ****************** */
    // 로그인
    $r->addRoute('POST', '/login', ['JWTController', 'createJwt']);   // JWT 생성: 로그인 + 해싱된 패스워드 검증 내용 추가
    // JWT 유효성 검사
    $r->addRoute('GET', '/jwt', ['JWTController', 'validateJwt']);
    // 회원가입
    $r->addRoute('POST', '/user-test', ['JWTController', 'createSignUpJwt']);
    // 카카오로그인
    $r->addRoute('POST', '/kakao-login', ['JWTController', 'createSocialJwt']);

    /* *********************************** 유저 ********************************************* */
    // 유저 정보 조회
    $r->addRoute('GET', '/user/{userIdx}', ['IndexController', 'getUser']);
    // 회원가입
    //$r->addRoute('POST', '/user', ['IndexController', 'signUp']);
    // 로그인
    //$r->addRoute('POST', '/login', ['IndexController', 'login']);
    // 유저 정보 수정
    $r->addRoute('PATCH', '/user/{userIdx}', ['IndexController', 'changeUser']);
    // 회원탈퇴
    $r->addRoute('DELETE', '/user/{userIdx}', ['IndexController', 'deleteUser']);


    // 동네 범위 설정
    $r->addRoute('POST', '/town-range', ['IndexController', 'setTownRange']);
    $r->addRoute('POST', '/town', ['IndexController', 'setTown']);

    //$r->addRoute('POST', '/social-login', ['IndexController', 'socialLogin']);
    //$r->addRoute('POST', '/naver-login', ['IndexController', 'naverLogin']);




//    $r->addRoute('GET', '/users', 'get_all_users_handler');
//    // {id} must be a number (\d+)
//    $r->addRoute('GET', '/user/{id:\d+}', 'get_user_handler');
//    // The /{title} suffix is optional
//    $r->addRoute('GET', '/articles/{id:\d+}[/{title}]', 'get_article_handler');
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

// 로거 채널 생성
$accessLogs = new Logger('ACCESS_LOGS');
$errorLogs = new Logger('ERROR_LOGS');
// log/your.log 파일에 로그 생성. 로그 레벨은 Info
$accessLogs->pushHandler(new StreamHandler('logs/access.log', Logger::INFO));
$errorLogs->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));
// add records to the log
//$log->addInfo('Info log');
// Debug 는 Info 레벨보다 낮으므로 아래 로그는 출력되지 않음
//$log->addDebug('Debug log');
//$log->addError('Error log');

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        echo "404 Not Found";
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        echo "405 Method Not Allowed";
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        switch ($routeInfo[1][0]) {
            case 'IndexController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/IndexController.php';
                break;
            case 'JWTController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/JWTController.php';
                break;
            case 'ProductController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/ProductController.php';
                break;
            case 'TownLifeController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/TownLifeController.php';
                break;
            /*case 'SearchController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/SearchController.php';
                break;
            case 'ReviewController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ReviewController.php';
                break;
            case 'ElementController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ElementController.php';
                break;
            case 'AskFAQController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/AskFAQController.php';
                break;*/
        }

        break;
}
