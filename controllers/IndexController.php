<?php
require 'function.php';

const JWT_SECRET_KEY = "TEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEY";

$res = (object)array();
header('Content-Type: json;charset=UTF-8');
$req = json_decode(file_get_contents("php://input"));
try {
    addAccessLogs($accessLogs, $req);
    switch ($handler) {
        case "index":
            echo "API Server";
            break;
        case "ACCESS_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/access.log");
            break;
        case "ERROR_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/errors.log");
            break;



    /* *********************************** 유저 ********************************************* */


//        case "signUp":
//            http_response_code(200);
//            if(empty($req->nickname)){
//                $res->isSuccess = False;
//                $res->code = 200;
//                $res->message = "닉네임을 입력하세요";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
//            if(empty($req->phoneNumber)) {
//                $res->isSuccess = False;
//                $res->code = 201;
//                $res->message = "전화번호를 입력하세요";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
//
//            if(empty($req->lat)|empty($req->lon)) {
//                $res->isSuccess = False;
//                $res->code = 202;
//                $res->message = "위치(위도,경도)를 입력하세요";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
//            if(!isValidAddress($req->lat,$req->lon)){
//                $res->isSuccess = False;
//                $res->code = 203;
//                $res->message = "저장되지 않은 위치입니다.";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
//            if(isValidNumber($req->phoneNumber)){
//                $res->isSuccess = False;
//                $res->code = 204;
//                $res->message = "중복된 폰 번호";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
//            if(isValidNickname($req->nickname)){
//                $res->isSuccess = False;
//                $res->code = 205;
//                $res->message = "중복된 닉네임";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
//            if(isValidID($req->id)){
//                $res->isSuccess = FALSE;
//                $res->code = 206;
//                $res->message = "중복된 아이디";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
//            // 비밀번호 밸리데이션
//            $address=getAddress($req->lat,$req->lon);
//            $pwd_hash = password_hash($req->pwd, PASSWORD_DEFAULT);
//
//            $res->result = signUp($req->nickname,$req->phoneNumber,$req->profilePhotoUrl,
//                $req->lat,$req->lon,$address['si'],$address['gu'],$address['dong'],$req->id,$pwd_hash);
//            $res->isSuccess = TRUE;
//            $res->code = 100;
//            $res->message = "회원가입 성공";
//            echo json_encode($res, JSON_NUMERIC_CHECK);
//            break;


//          1)
//        case "login":
//            http_response_code(200);
//            if(!isValidNumber($req->phoneNumber)){
//                $res->isSuccess = FALSE;
//                $res->code = 200;
//                $res->message = "로그인 실패";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
//
//            $res->isSuccess = TRUE;
//            $res->code = 100;
//            $res->message = "로그인 성공";
//            echo json_encode($res, JSON_NUMERIC_CHECK);
//            break;
//           2)
//        case "login":
//            http_response_code(200);
//            if(!isValidID($req->id)){
//                $res->isSuccess = FALSE;
//                $res->code = 200;
//                $res->message = "유효하지 않은 아이디";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
//            if(!isPW($req->id, $req->pwd)){
//                $res->isSuccess = FALSE;
//                $res->code = 200;
//                $res->message = "비밀번호 일치실패";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
//            $res->isSuccess = TRUE;
//            $res->code = 100;
//            $res->message = "로그인 성공";
//            echo json_encode($res, JSON_NUMERIC_CHECK);
//            break;


        // 유저 정보 수정
        case "changeUser":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if($userIdxInToken != $vars['userIdx']){
                $res->isSuccess = FALSE;
                $res->code = 300;
                $res->message = "권한이 없는 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
//            if(empty($req->nickname)){
//                $res->isSuccess = False;
//                $res->code = 200;
//                $res->message = "닉네임을 입력하세요";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
            changeUser($vars['userIdx'],$req->profilePhotoUrl,$req->nickname);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "유저정보 수정 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;



        // 회원탈퇴
        case "deleteUser":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if($userIdxInToken != $vars['userIdx']){
                $res->isSuccess = FALSE;
                $res->code = 300;
                $res->message = "권한이 없는 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
//            if (!isUser($vars['userIdx'])){
//
//                $res->isSuccess = False;
//                $res->code = 200;
//                $res->message = "유효하지 않은 유저";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }

            deleteUser($vars['userIdx']);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "회원탈퇴 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        case "getUser":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if($userIdxInToken != $vars['userIdx']){
                $res->isSuccess = FALSE;
                $res->code = 300;
                $res->message = "권한이 없는 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $res->result = getUser($vars['userIdx']);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "유저 세부조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;


        case "setTownRange":
            http_response_code(200);

            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            setTownRange($userIdxInToken,$req->townRange);
            //$res->result =
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "동네범위설정 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        case "setTown":
            http_response_code(200);

            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            $address=getAddress($req->lat,$req->lon);
            setTown($userIdxInToken,$req->lat,$req->lon,$address['si'],$address['gu'],$address['dong']);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "동네설정 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

//        case "socialLogin":
//            http_response_code(200);
//
//            socialLogin($req->accessToken);
//            //$res->result =
//            $res->isSuccess = TRUE;
//            $res->code = 100;
//            $res->message = "소셜디비추가 성공";
//            echo json_encode($res, JSON_NUMERIC_CHECK);
//            break;

//        case "naverLogin":
//            http_response_code(200);
//
////            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
////            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
////            if (empty($jwt)){
////                $res->isSuccess = FALSE;
////                $res->code = 400;
////                $res->message = "토큰을 입력하세요";
////                echo json_encode($res, JSON_NUMERIC_CHECK);
////                addErrorLogs($errorLogs, $res, $req);
////                return;
////            }
////            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
////                $res->isSuccess = FALSE;
////                $res->code = 401;
////                $res->message = "유효하지 않은 토큰입니다";
////                echo json_encode($res, JSON_NUMERIC_CHECK);
////                addErrorLogs($errorLogs, $res, $req);
////                return;
////            }
//
//            naverLogin($req->accessToken);
//            //$res->result =
//            $res->isSuccess = TRUE;
//            $res->code = 100;
//            $res->message = "네이버로그인 성공";
//            echo json_encode($res, JSON_NUMERIC_CHECK);
//            break;

    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
