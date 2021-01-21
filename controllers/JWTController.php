<?php
require 'function.php';

const JWT_SECRET_KEY = "TEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEY";

$res = (Object)Array();
header('Content-Type: json');
$req = json_decode(file_get_contents("php://input"));
try {
    addAccessLogs($accessLogs, $req);
    switch ($handler) {
        /*
         * API No. 1
         * API Name : JWT 생성 테스트 API (로그인)
         * 마지막 수정 날짜 : 20.08.29
         */
        case "createSignUpJwt":
            http_response_code(200);
            // 1) 로그인 시 email, password 받기
            if(empty($req->nickname)){
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "닉네임을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(empty($req->phoneNumber)) {
                $res->isSuccess = False;
                $res->code = 201;
                $res->message = "전화번호를 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            if(empty($req->lat)|empty($req->lon)) {
                $res->isSuccess = False;
                $res->code = 202;
                $res->message = "위치(위도,경도)를 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(!isValidAddress($req->lat,$req->lon)){
                $res->isSuccess = False;
                $res->code = 203;
                $res->message = "저장되지 않은 위치입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(isValidNumber($req->phoneNumber)){
                $res->isSuccess = False;
                $res->code = 204;
                $res->message = "중복된 폰 번호";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(isValidNickname($req->nickname)){
                $res->isSuccess = False;
                $res->code = 205;
                $res->message = "중복된 닉네임";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(isValidID($req->id)){
                $res->isSuccess = FALSE;
                $res->code = 206;
                $res->message = "중복된 아이디";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            // 비밀번호 밸리데이션
            $address=getAddress($req->lat,$req->lon);
            $pwd_hash = password_hash($req->pwd, PASSWORD_DEFAULT);

            $res->result->userIdx = createSignUpJwt($req->nickname,$req->phoneNumber,$req->profilePhotoUrl,
                $req->lat,$req->lon,$address['si'],$address['gu'],$address['dong'],$req->id,$pwd_hash);


            // 2) JWT 발급
            // Payload에 맞게 다시 설정 요함, 아래는 Payload에 userIdx를 넣기 위한 과정
            $userIdx = getUserIdxByID($req->id);  // JWTPdo.php 에 구현
            //$townLifeIdxList = getTownLifeIdxList($req->userID); // 리스트로 구현해야하는딩
            //$commentIdxList  = getCommentIdxList($req->userID);
            //$productIdxList  = getProductIdxList($req->userID);

            $jwt = getJWT($userIdx, JWT_SECRET_KEY); // function.php 에 구현
            $res->result->jwt = $jwt;
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "로그인 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;


        case "createJwt":
            http_response_code(200);
            // 1) 로그인 시 email, password 받기
            if (!isValidUser($req->userID, $req->pwd)) { // JWTPdo.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 아이디 입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            // 2) JWT 발급
            // Payload에 맞게 다시 설정 요함, 아래는 Payload에 userIdx를 넣기 위한 과정
            $userIdx = getUserIdxByID($req->userID);  // JWTPdo.php 에 구현
            //$townLifeIdxList = getTownLifeIdxList($req->userID); // 리스트로 구현해야하는딩
            //$commentIdxList  = getCommentIdxList($req->userID);
            //$productIdxList  = getProductIdxList($req->userID);

            $jwt = getJWT($userIdx, JWT_SECRET_KEY); // function.php 에 구현
            $res->result->jwt = $jwt;
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "로그인 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        case "createSocialJwt":
            //echo '시작';
            //아이디 중복인사람 걸러내기
            //액세스토큰속아이디
            $USER_API_URL= "https://kapi.kakao.com/v2/user/me";
            $opts = array( CURLOPT_URL => $USER_API_URL,
                CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSLVERSION => 1,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array( "Authorization: Bearer " . $req->accessToken ) );

            $curlSession = curl_init();
            curl_setopt_array($curlSession, $opts);
            $accessUserJson = curl_exec($curlSession);
            curl_close($curlSession);

            $me_responseArr = json_decode($accessUserJson, true);
            if ($me_responseArr['id']) {
                $mb_uid = 'kakao_'.$me_responseArr['id'];
                $mb_nickname = $me_responseArr['properties']['nickname']; // 닉네임
                $mb_profile_image = $me_responseArr['properties']['profile_image']; // 프로필 이미지

            }
            else{
                echo "카카오 아이디를 받아올 수 없습니다.";
                break;
            }


            if (isValidUser2($mb_uid)) { // JWTPdo.php 에 구현
                $userIdx=kakaoLogin2($mb_uid); // 함수를 바꿔 유저idx로 가져오는
                $jwt = getJWT($userIdx, JWT_SECRET_KEY);
                $res->result->jwt = $jwt;
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "소셜로그인 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            else{
                $userIdx=kakaoLogin($req->accessToken);
                $jwt = getJWT($userIdx, JWT_SECRET_KEY); // function.php 에 구현
                $res->result->jwt = $jwt;
                $res->isSuccess = TRUE;
                $res->code = 101;
                $res->message = "소셜로그인 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }





        /*
         * API No. 2
         * API Name : JWT 유효성 검사 테스트 API
         * 마지막 수정 날짜 : 20.08.29
         */
        
        case "validateJwt":

            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            // 1) JWT 유효성 검사
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 200;
                $res->message = "유효하지 않은 토큰입니다"; 
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            // 2) JWT Payload 반환
            http_response_code(200);
            $res->result = getDataByJWToken($jwt, JWT_SECRET_KEY);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "유효성 검사 성공";

            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;


    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
