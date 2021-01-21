<?php



/* *********************************** 동네생활 ********************************************* */

// 글조회 1) 키워드, 카테고리 검색 x
function getTownLifeIndex(){
    $pdo = pdoSqlConnect();
    $query = "select townLifeIdx from TownLife where isDeleted !='Y' order by createdAt desc limit 1;";

    $st = $pdo->prepare($query);
    $st->execute();

    $row = $st -> fetchColumn(); // 컬럼하나의 값만!
    //$st = null;
    $pdo = null;
    return $row;
}

function isValidTownLife($townLifeIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(
    select t.townLifeIdx
from TownLife as t
join User as u on u.userIdx=t.userIdx
where t.townLifeIdx = ? and t.isDeleted !='Y' and u.isDeleted='N') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}

function getContentOne($townLifeIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "
SELECT t.townLifeIdx,
       u.userIdx,
       CASE
           WHEN exists(select s.userIdx from TownLifeSympathy as s where s.userIdx = ?
                                                                     and s.townLifeIdx=t.townLifeIdx)
               THEN 'Y'
           ELSE 'N'
           END             AS isSympathy,
       (select tcy.categoryName from TownLifeCategory as tcy where t.categoryIdx=tcy.categoryIdx) as categoryName,
       CASE
           WHEN u.profilePhotoUrl IS NULL
               THEN 'http://default'
           ELSE u.profilePhotoUrl
           END AS profilePhotoUrl,
       CASE
           WHEN exists((select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where t.userIdx=atb.userIdx)))
           THEN (select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where t.userIdx=atb.userIdx))
           ELSE 0
           END AS badgeUrl,
       u.nickname,
       CASE
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 1
               THEN u.dong
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 5
               THEN concat(u.gu, ' ', u.dong)
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 10
               THEN concat(u.si, ' ', u.gu, ' ', u.dong)
           END                                                AS address,
       concat('인증 ', cast(u.certificationCount as char), '회') AS certificationCount,
       t.content,
       (select count(tc.townLifeIdx) from TownLifeComment as tc where t.townLifeIdx = tc.townLifeIdx) as commentCount,
       (select count(ts.townLifeIdx) from TownLifeSympathy as ts where t.townLifeIdx = ts.townLifeIdx) as sympathyCount,
       CASE
           WHEN TIMESTAMPDIFF(SECOND, t.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(SECOND, t.createdAt, current_timestamp()) as char), '초 전')
           WHEN TIMESTAMPDIFF(MINUTE, t.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(MINUTE, t.createdAt, current_timestamp()) as char), '분 전')
           WHEN TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) < 24
               THEN concat(cast(TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) as char), '시간 전')
           WHEN TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) = 24
               THEN '어제'
           WHEN TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) < 7
               THEN concat(cast(TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) as char), '일 전')
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 7) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 14
               THEN '지난 주'
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 14) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 21
               THEN '2주 전'
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 21) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 28
               THEN '3주 전'
           ELSE '미정'
           END                                                AS time
FROM User AS u
         JOIN TownLife AS t ON u.userIdx = t.userIdx,User as us
WHERE us.userIdx = ? and t.townLifeIdx =? and t.isDeleted !='Y' and u.isDeleted='N'
        and (6371 *acos(cos(radians(us.lat)) * cos(radians(u.lat)) * cos(radians(u.lon) - radians(us.lon))
           + sin(radians(us.lat)) * sin(radians(u.lat)))) <(select townRange from User where userIdx=?)
ORDER BY t.createdAt DESC;
";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$userIdxInToken,$townLifeIdx,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}
function getContentImg($townLifeIdx)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT townLifePhotoUrl FROM TownLifePhoto WHERE townLifeIdx = ? and isDeleted !='Y';";
    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();
    // fetcho one으로 해서 for문돌리기 행전체수만큼

    $st = null;
    $pdo = null;

    return $res;
}

function hasImage($idx){
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select townLifeIdx from TownLifePhoto where townLifeIdx = ? and isDeleted !='Y') exist;";
    $st = $pdo->prepare($query);
    $st->execute([$idx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}



// 글조회 2) 키워드 검색
function isValidKeywordContent($keyword)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(
    select * from TownLife as t
join User as u on u.userIdx=t.userIdx
where t.isDeleted !='Y' and t.content like concat('%',?,'%') and u.isDeleted='N';) exist;";

    $st = $pdo->prepare($query);
    $st->execute([$keyword]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}


function getTownLifeIndexKeyword($keyword){
    $pdo = pdoSqlConnect();
    $query = "select t.townLifeIdx
from TownLife as t
join User as u on u.userIdx=t.userIdx
where t.content like concat('%',?,'%') and t.isDeleted !='Y' and u.isDeleted='N'
order by t.createdAt desc limit 1;";

    $st = $pdo->prepare($query);
    $st->execute([$keyword]);

    $row = $st -> fetchColumn();
    $pdo = null;
    return $row;
}
function isValidTownLifeKeyword($townLifeIdx,$keyword)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select  t.townLifeIdx
from TownLife as t
join User as u on u.userIdx=t.userIdx
where t.townLifeIdx = ? and t.content like concat('%',?,'%') and t.isDeleted !='Y'and u.isDeleted='N') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx,$keyword]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}


function getKeywordContentOne($townLifeIdx,$keyword,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "
SELECT t.townLifeIdx,
       u.userIdx,
       CASE
           WHEN exists(select s.userIdx from TownLifeSympathy as s where s.userIdx = ?
                                                                     and s.townLifeIdx=t.townLifeIdx)
               THEN 'Y'
           ELSE 'N'
           END             AS isSympathy,
       (select tcy.categoryName from TownLifeCategory as tcy where t.categoryIdx=tcy.categoryIdx) as categoryName,
       CASE
           WHEN u.profilePhotoUrl IS NULL
               THEN 'http://default'
           ELSE u.profilePhotoUrl
           END AS profilePhotoUrl,
       CASE
           WHEN exists((select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where t.userIdx=atb.userIdx)))
           THEN (select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where t.userIdx=atb.userIdx))
           ELSE 0
           END AS badgeUrl,
       u.nickname,
       CASE
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 1
               THEN u.dong
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 5
               THEN concat(u.gu, ' ', u.dong)
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 10
               THEN concat(u.si, ' ', u.gu, ' ', u.dong)
           END                                                AS address,
       concat('인증 ', cast(u.certificationCount as char), '회') AS certificationCount,
       t.content,
       (select count(tc.townLifeIdx) from TownLifeComment as tc where t.townLifeIdx = tc.townLifeIdx) as commentCount,
       (select count(ts.townLifeIdx) from TownLifeSympathy as ts where t.townLifeIdx = ts.townLifeIdx) as sympathyCount,
       CASE
           WHEN TIMESTAMPDIFF(SECOND, t.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(SECOND, t.createdAt, current_timestamp()) as char), '초 전')
           WHEN TIMESTAMPDIFF(MINUTE, t.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(MINUTE, t.createdAt, current_timestamp()) as char), '분 전')
           WHEN TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) < 24
               THEN concat(cast(TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) as char), '시간 전')
           WHEN TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) = 24
               THEN '어제'
           WHEN TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) < 7
               THEN concat(cast(TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) as char), '일 전')
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 7) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 14
               THEN '지난 주'
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 14) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 21
               THEN '2주 전'
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 21) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 28
               THEN '3주 전'
           ELSE '미정'
           END                                                AS time
FROM User AS u
         JOIN TownLife AS t ON u.userIdx = t.userIdx,User as us
WHERE us.userIdx = ? and t.townLifeIdx =? and t.content like concat('%',?,'%') and t.isDeleted !='Y' and u.isDeleted ='N'
        and (6371 *acos(cos(radians(us.lat)) * cos(radians(u.lat)) * cos(radians(u.lon) - radians(us.lon))
           + sin(radians(us.lat)) * sin(radians(u.lat)))) < (select townRange from User where userIdx=?)
ORDER BY t.createdAt DESC;
";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$userIdxInToken,$townLifeIdx,$keyword,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

// 글조회 3) 카테고리 검색
function isValidCategoryContent($category)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select * 
from TownLife as t
join User as u on u.userIdx=t.userIdx 
where t.categoryIdx = ? and t.isDeleted !='Y' and u.isDeleted='N') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$category]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}


function getTownLifeIndexCategory($category){
    $pdo = pdoSqlConnect();
    $query = "select t.townLifeIdx
from TownLife as t
join User as u on u.userIdx=t.userIdx
where t.categoryIdx = ? and t.isDeleted !='Y' and u.isDeleted='N'
order by  t.createdAt desc limit 1;";

    $st = $pdo->prepare($query);
    $st->execute([$category]);

    $row = $st -> fetchColumn();
    //$st = null;
    $pdo = null;
    return $row;
}


function isValidTownLifeCategory($townLifeIdx,$category)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select t.townLifeIdx
from TownLife as t
join User as u on u.userIdx=t.userIdx
where t.townLifeIdx = ? and t.categoryIdx =? and t.isDeleted !='Y' and u.isDeleted='N') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx,$category]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}


function getCategoryContentOne($townLifeIdx,$category,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "
SELECT t.townLifeIdx,
       u.userIdx,
       CASE
           WHEN exists(select s.userIdx from TownLifeSympathy as s where s.userIdx = ?
                                                                     and s.townLifeIdx=t.townLifeIdx)
               THEN 'Y'
           ELSE 'N'
           END             AS isSympathy,
       (select tcy.categoryName from TownLifeCategory as tcy where t.categoryIdx=tcy.categoryIdx) as categoryName,
       CASE
           WHEN u.profilePhotoUrl IS NULL
               THEN 'http://default'
           ELSE u.profilePhotoUrl
           END AS profilePhotoUrl,
       CASE
           WHEN exists((select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where t.userIdx=atb.userIdx)))
           THEN (select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where t.userIdx=atb.userIdx))
           ELSE 0
           END AS badgeUrl,
       u.nickname,
       CASE
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 1
               THEN u.dong
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 5
               THEN concat(u.gu, ' ', u.dong)
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 10
               THEN concat(u.si, ' ', u.gu, ' ', u.dong)
           END                                                AS address,
       concat('인증 ', cast(u.certificationCount as char), '회') AS certificationCount,
       t.content,
       (select count(tc.townLifeIdx) from TownLifeComment as tc where t.townLifeIdx = tc.townLifeIdx) as commentCount,
       (select count(ts.townLifeIdx) from TownLifeSympathy as ts where t.townLifeIdx = ts.townLifeIdx) as sympathyCount,
       CASE
           WHEN TIMESTAMPDIFF(SECOND, t.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(SECOND, t.createdAt, current_timestamp()) as char), '초 전')
           WHEN TIMESTAMPDIFF(MINUTE, t.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(MINUTE, t.createdAt, current_timestamp()) as char), '분 전')
           WHEN TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) < 24
               THEN concat(cast(TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) as char), '시간 전')
           WHEN TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) = 24
               THEN '어제'
           WHEN TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) < 7
               THEN concat(cast(TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) as char), '일 전')
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 7) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 14
               THEN '지난 주'
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 14) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 21
               THEN '2주 전'
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 21) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 28
               THEN '3주 전'
           ELSE '미정'
           END                                                AS time
FROM User AS u
         JOIN TownLife AS t ON u.userIdx = t.userIdx,User as us
WHERE us.userIdx = ? and t.townLifeIdx =? and t.categoryIdx = ? and t.isDeleted !='Y' and u.isDeleted ='N'
        and (6371 *acos(cos(radians(us.lat)) * cos(radians(u.lat)) * cos(radians(u.lon) - radians(us.lon))
           + sin(radians(us.lat)) * sin(radians(u.lat)))) < (select townRange from User where userIdx=?)
ORDER BY t.createdAt DESC;
";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$userIdxInToken,$townLifeIdx,$category,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

// 글조회 4) 카테고리, 키워드 검색
function getTownLifeIndexCategoryKeyword($category,$keyword){
    $pdo = pdoSqlConnect();
    $query = "select t.townLifeIdx
from TownLife as t
join User as u on u.userIdx=t.userIdx
where t.content like concat('%',?,'%') and t.categoryIdx = ? and t.isDeleted !='Y' and u.isDeleted='N'
order by t.createdAt desc limit 1;";

    $st = $pdo->prepare($query);
    $st->execute([$keyword,$category]);

    $row = $st -> fetchColumn();
    //$st = null; //왜안한거니??
    $pdo = null;
    return $row;
}


function isValidTownLifeCategoryKeyword($townLifeIdx,$category,$keyword)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select t.townLifeIdx
from TownLife as t
join User as u on u.userIdx=t.userIdx 
where t.townLifeIdx = ? and t.content like concat('%',?,'%') and t.categoryIdx =? and t.isDeleted !='Y'and u.isDeleted='N') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx,$keyword,$category]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}


// 동네생활 글 조회
function getCategoryKeywordContentOne($userIdxInToken,$townLifeIdx,$category,$keyword)
{
    $pdo = pdoSqlConnect();
    $query = "
SELECT t.townLifeIdx,
       u.userIdx,
       CASE
           WHEN exists(select s.userIdx from TownLifeSympathy as s where s.userIdx = ?
                                                                     and s.townLifeIdx=t.townLifeIdx)
               THEN 'Y'
           ELSE 'N'
           END             AS isSympathy,
       (select tcy.categoryName from TownLifeCategory as tcy where t.categoryIdx=tcy.categoryIdx) as categoryName,
       CASE
           WHEN u.profilePhotoUrl IS NULL
               THEN 'http://default'
           ELSE u.profilePhotoUrl
           END AS profilePhotoUrl,
       CASE
           WHEN exists((select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where t.userIdx=atb.userIdx)))
           THEN (select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where t.userIdx=atb.userIdx))
           ELSE 0
           END AS badgeUrl,
       u.nickname,
       CASE
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 1
               THEN u.dong
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 5
               THEN concat(u.gu, ' ', u.dong)
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 10
               THEN concat(u.si, ' ', u.gu, ' ', u.dong)
           END                                                AS address,
       concat('인증 ', cast(u.certificationCount as char), '회') AS certificationCount,
       t.content,
       (select count(tc.townLifeIdx) from TownLifeComment as tc where t.townLifeIdx = tc.townLifeIdx) as commentCount,
       (select count(ts.townLifeIdx) from TownLifeSympathy as ts where t.townLifeIdx = ts.townLifeIdx) as sympathyCount,
       CASE
           WHEN TIMESTAMPDIFF(SECOND, t.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(SECOND, t.createdAt, current_timestamp()) as char), '초 전')
           WHEN TIMESTAMPDIFF(MINUTE, t.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(MINUTE, t.createdAt, current_timestamp()) as char), '분 전')
           WHEN TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) < 24
               THEN concat(cast(TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) as char), '시간 전')
           WHEN TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) = 24
               THEN '어제'
           WHEN TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) < 7
               THEN concat(cast(TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) as char), '일 전')
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 7) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 14
               THEN '지난 주'
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 14) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 21
               THEN '2주 전'
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 21) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 28
               THEN '3주 전'
           ELSE '미정'
           END                                                AS time
FROM User AS u
         JOIN TownLife AS t ON u.userIdx = t.userIdx,User as us
WHERE us.userIdx = ? and t.townLifeIdx =? and t.categoryIdx = ? and  t.content like concat('%',?,'%') and t.isDeleted !='Y'  and u.isDeleted ='N'
        and (6371 *acos(cos(radians(us.lat)) * cos(radians(u.lat)) * cos(radians(u.lon) - radians(us.lon))
           + sin(radians(us.lat)) * sin(radians(u.lat)))) < (select townRange from User where userIdx=?)

ORDER BY t.createdAt DESC;
";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$userIdxInToken,$townLifeIdx,$category,$keyword,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}



// 글 세부 내용 조회
function getContentDetail($userIdxInToken,$townLifeIdx)
{
    $pdo = pdoSqlConnect();
    $query = "
SELECT t.townLifeIdx,
       u.userIdx,
       u.nickname,
       CASE
           WHEN u.profilePhotoUrl IS NULL
               THEN 'http://default'
           ELSE u.profilePhotoUrl
           END AS profilePhotoUrl,
       CASE
           WHEN exists((select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where t.userIdx=atb.userIdx)))
           THEN (select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where t.userIdx=atb.userIdx))
           ELSE 0
           END AS badgeUrl,
       (select tcy.categoryName from TownLifeCategory as tcy where t.categoryIdx=tcy.categoryIdx) as categoryName,
       t.content,
       CASE
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 1
               THEN u.dong
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 5
               THEN concat(u.gu, ' ', u.dong)
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 10
               THEN concat(u.si, ' ', u.gu, ' ', u.dong)
           END                                                AS address,
       concat('인증 ', cast(u.certificationCount as char), '회') AS certificationCount,
       CASE
           WHEN TIMESTAMPDIFF(SECOND, t.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(SECOND, t.createdAt, current_timestamp()) as char), '초 전')
           WHEN TIMESTAMPDIFF(MINUTE, t.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(MINUTE, t.createdAt, current_timestamp()) as char), '분 전')
           WHEN TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) < 24
               THEN concat(cast(TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) as char), '시간 전')
           WHEN TIMESTAMPDIFF(HOUR, t.createdAt, current_timestamp()) = 24
               THEN '어제'
           WHEN TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) < 7
               THEN concat(cast(TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) as char), '일 전')
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 7) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 14
               THEN '지난 주'
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 14) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 21
               THEN '2주 전'
           WHEN (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp()) >= 21) and
                (TIMESTAMPDIFF(DAY, t.createdAt, current_timestamp())) < 28
               THEN '3주 전'
           ELSE '미정'
           END                                                AS time,
       (select count(tc.townLifeIdx) from TownLifeComment as tc where t.townLifeIdx = tc.townLifeIdx) as commentCount,
       (select count(ts.townLifeIdx) from TownLifeSympathy as ts where t.townLifeIdx = ts.townLifeIdx) as sympathyCount,
       CASE
           WHEN exists(select s.userIdx from TownLifeSympathy as s where s.userIdx = ? and s.townLifeIdx=?)
               THEN 'Y'
           ELSE 'N'
           END             AS isSympathy

FROM TownLife AS t
         INNER JOIN User AS u ON t.userIdx = u.userIdx , User as us
WHERE us.userIdx = ? and t.townLifeIdx = ? and t.isDeleted !='Y' and u.isDeleted='N';
";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$townLifeIdx,$userIdxInToken,$townLifeIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}


//글 세부내용 댓글 조회
function getComment($townLifeIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "
SELECT tc.townLifeIdx,
       tc.commentIdx,
       u.userIdx,
       CASE
           WHEN u.profilePhotoUrl IS NULL
               THEN 'http://default'
           ELSE u.profilePhotoUrl
           END AS profilePhotoUrl,
       CASE
           WHEN exists((select abc.badgeUrl
                        from ActivityBadgeCategory as abc
                        where abc.badgeIdx
                                  =
                              (select atb.topBadgeIdx from ActivityTopBadge as atb where tc.userIdx = atb.userIdx)))
               THEN (select abc.badgeUrl
                     from ActivityBadgeCategory as abc
                     where abc.badgeIdx
                               = (select atb.topBadgeIdx from ActivityTopBadge as atb where tc.userIdx = atb.userIdx))
           ELSE 0
           END AS badgeUrl,
       u.nickname,
       CASE
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 1
               THEN u.dong
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 5
               THEN concat(u.gu, ' ', u.dong)
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 10
               THEN concat(u.si, ' ', u.gu, ' ', u.dong)
           END                                                AS address,
       CASE
           WHEN TIMESTAMPDIFF(SECOND, tc.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(SECOND, tc.createdAt, current_timestamp()) as char), '초 전')
           WHEN TIMESTAMPDIFF(MINUTE, tc.createdAt, current_timestamp()) < 60
               THEN concat(cast(TIMESTAMPDIFF(MINUTE, tc.createdAt, current_timestamp()) as char), '분 전')
           WHEN TIMESTAMPDIFF(HOUR, tc.createdAt, current_timestamp()) < 24
               THEN concat(cast(TIMESTAMPDIFF(HOUR, tc.createdAt, current_timestamp()) as char), '시간 전')
           WHEN TIMESTAMPDIFF(HOUR, tc.createdAt, current_timestamp()) = 24
               THEN '어제'
           WHEN TIMESTAMPDIFF(DAY, tc.createdAt, current_timestamp()) < 7
               THEN concat(cast(TIMESTAMPDIFF(DAY, tc.createdAt, current_timestamp()) as char), '일 전')
           WHEN (TIMESTAMPDIFF(DAY, tc.createdAt, current_timestamp()) >= 7) and
                (TIMESTAMPDIFF(DAY, tc.createdAt, current_timestamp())) < 14
               THEN '지난 주'
           WHEN (TIMESTAMPDIFF(DAY, tc.createdAt, current_timestamp()) >= 14) and
                (TIMESTAMPDIFF(DAY, tc.createdAt, current_timestamp())) < 21
               THEN '2주 전'
           WHEN (TIMESTAMPDIFF(DAY, tc.createdAt, current_timestamp()) >= 21) and
                (TIMESTAMPDIFF(DAY, tc.createdAt, current_timestamp())) < 28
               THEN '3주 전'
           ELSE '미정'
           END                                                AS time,
       (select count(tcs.commentIdx) from TownLifeCommentSympathy as tcs where tc.commentIdx = tcs.commentIdx) as sympathyCount,
       tc.comment
FROM TownLifeComment AS tc
         JOIN User AS u ON u.userIdx = tc.userIdx, User as us
WHERE tc.townLifeIdx = ? and us.userIdx=? and tc.isDeleted !='Y' and u.isDeleted='N'
ORDER BY if(tc.parentIdx = 0, tc.commentIdx, tc.parentIdx), tc.createdAt;
";

    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}
//글 등록
function createContent($categoryIdx,$content,$imgList,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $queryContent = "INSERT INTO TownLife (userIdx,categoryIdx,content) VALUES (?,?,?);";
    $queryImg = "INSERT INTO TownLifePhoto (townLifeIdx,townLifePhotoUrl,sequence) VALUES (?,?,?);";

    try {

        $st1 = $pdo->prepare($queryContent);
        $st2 = $pdo->prepare($queryImg);

        $pdo->beginTransaction();

        $st1->execute([$userIdxInToken,$categoryIdx,$content]);
        $townLifeIdx = $pdo->lastInsertId();
        $sequence = 1;
        foreach ($imgList as $img) {
            $st2->execute([$townLifeIdx, $img, $sequence++]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
            // If we got here our two data updates are not in the database
        }
        //throw $e;
        return $e->getMessage();
    }

    $st1 = null;
    $st2 = null;
    $pdo = null;
    return ['productIdx'=>$townLifeIdx];
}




function isContentCategory($categoryIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select categoryIdx from TownLifeCategory where categoryIdx = ?) exist;";

    $st = $pdo->prepare($query);
    $st->execute([$categoryIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}

// 글 수정
function changeContent($townLifeIdx,$categoryIdx,$content,$imgList)
{
    $pdo = pdoSqlConnect();
    $queryContent = "UPDATE TownLife SET categoryIdx=if(isnull(?),categoryIdx,?), content=if(isnull(?),content,?) WHERE townLifeIdx=?;";
    $queryDeleteImg = "delete from TownLifePhoto where townLifeIdx=?;";
    $queryInsertImg = "insert into TownLifePhoto (townLifeIdx,townLifePhotoUrl,sequence) values (?,?,?);";


    try {

        $st1 = $pdo->prepare($queryContent);
        if (!empty($imgList)){
            $st2 = $pdo->prepare($queryDeleteImg);
        }
        $st3 = $pdo->prepare($queryInsertImg);

        $pdo->beginTransaction();

        $st1->execute([$categoryIdx,$categoryIdx,$content,$content,$townLifeIdx]);
        if (!empty($imgList)){
            $st2->execute([$townLifeIdx]);
        }

        $sequence = 1;
        foreach ($imgList as $img) {
            $st3->execute([$townLifeIdx, $img, $sequence++]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
            // If we got here our two data updates are not in the database
        }
        //throw $e;
        return $e->getMessage();
    }

    $st1 = null;
    $st2 = null;
    $st3 = null;
    $pdo = null;
    // return "성공";
}
function isValidChangeContent($userIdxInToken,$townLifeIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select townLifeIdx from TownLife where userIdx = ? and townLifeIdx=? and isDeleted != 'Y' )exist;";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$townLifeIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}

function isValidChangeComment($userIdxInToken,$commentIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select commentIDx from TownLifeComment where userIdx = ? and commentIdx=? and isDeleted != 'Y' )exist;";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$commentIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function clickContentSympathy($townLifeIdx,$sympathyIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO TownLifeSympathy (townLifeIdx,sympathyIdx,userIdx) VALUES (?,?,?);";

    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx,$sympathyIdx,$userIdxInToken]);

    $st = null;
    $pdo = null;

    return ['townLifeIdx'=>$townLifeIdx,"userIdx"=>$userIdxInToken,"sympathyIdx"=>$sympathyIdx];
}


function deleteContentSympathy($townLifeIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "delete from TownLifeSympathy where townLifeIdx = ? and userIdx = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx,$userIdxInToken]);

    $st = null;
    $pdo = null;

    //return "성공";
}

function isValidSympathy($sympathyIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select sympathyIdx from SympathyCategory where sympathyIdx = ?) exist;";

    $st = $pdo->prepare($query);
    $st->execute([$sympathyIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}


function isSympathyContent($townLifeIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select townLifeIdx from TownLifeSympathy where townLifeIdx = ? and userIdx =? and  isDeleted !='Y') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}


// 글 공감 유저 목록 조회
function getContentSympathy($userIdxInToken,$townLifeIdx)
{
    $pdo = pdoSqlConnect();
    $query = "
select ts.userIdx,
       ts.sympathyIdx,
       u.nickname,
       CASE
           WHEN u.profilePhotoUrl IS NULL
               THEN 'http://default'
           ELSE u.profilePhotoUrl
           END AS profilePhotoUrl,
       CASE
           WHEN exists((select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select topBadgeIdx from ActivityTopBadge as atb where u.userIdx=atb.userIdx)))
           THEN (select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select topBadgeIdx from ActivityTopBadge as atb where u.userIdx=atb.userIdx))
           ELSE 0
           END AS badgeUrl,
              CASE
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 1
               THEN u.dong
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 5
               THEN concat(u.gu, ' ', u.dong)
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 10
               THEN concat(u.si, ' ', u.gu, ' ', u.dong)
           END                                       AS address,
       concat('인증 ',u.certificationCount,'회') as certificationCount,
       ts.sympathyIdx
from TownLifeSympathy as ts
         JOIN User as u ON ts.userIdx = u.userIdx , User as us
where us.userIdx=? and ts.townLifeIdx = ? and u.isDeleted='N';
";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$townLifeIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function getSympathyCount($townLifeIdx)
{
    $pdo = pdoSqlConnect();
    // $query = "select count(townLifeIdx) as sympathyCount from TownLifeSympathy where townLifeIdx=? and isDeleted !='Y';";
    $query="
select count(ts.townLifeIdx) as sympathyCount
from TownLifeSympathy as ts
join User as u on ts.userIdx=u.userIdx
where ts.townLifeIdx=? and ts.isDeleted !='Y' and u.isDeleted='N';";
    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx]);


    $row = $st -> fetchColumn();
    $st = null;
    $pdo = null;
    return $row;

}

function clickCommentSympathy($townLifeIdx,$sympathyIdx,$commentIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO TownLifeCommentSympathy (townLifeIdx,sympathyIdx,commentIdx,userIdx) VALUES (?,?,?,?);";
    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx,$sympathyIdx,$commentIdx,$userIdxInToken]);

    $st = null;
    $pdo = null;

    return ["userIdx"=>$userIdxInToken,'townLifeIdx'=>$townLifeIdx,'commentIdx'=>$commentIdx,'sympathyIdx'=>$sympathyIdx];

}


function getTLIdx($commentIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select townLifeIdx from TownLifeComment where commentIdx=? and  isDeleted !='Y';";


    $st = $pdo->prepare($query);
    $st->execute([$commentIdx]);

    $row = $st -> fetchColumn();
    //$st = null;
    $pdo = null;
    return $row;
}

function deleteCommentSympathy($commentIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "delete from TownLifeCommentSympathy where commentIdx =? and userIdx=?";
    $st = $pdo->prepare($query);
    $st->execute([$commentIdx,$userIdxInToken]);

    $st = null;
    $pdo = null;

    return "성공";

}

function isSympathyComment($commentIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select commentIdx from TownLifeCommentSympathy where commentIdx = ? and userIdx=? and  isDeleted !='Y') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$commentIdx,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}



function isValidComment($commentIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(
    select tc.commentIdx
from TownLifeComment as tc
join User as u on u.userIdx=tc.userIdx
where tc.commentIdx = ? and  tc.isDeleted !='Y' and u.isDeleted='N') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$commentIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}


// 댓글 공감 유저 목록 조회
function getCommentSympathy($commentIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "select tcs.userIdx,
       tcs.sympathyIdx,
       u.nickname,
       CASE
           WHEN u.profilePhotoUrl IS NULL
               THEN 'http://default'
           ELSE u.profilePhotoUrl
           END AS profilePhotoUrl,
       CASE
           WHEN exists((select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select topBadgeIdx from ActivityTopBadge as atb where u.userIdx=atb.userIdx)))
           THEN (select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select topBadgeIdx from ActivityTopBadge as atb where u.userIdx=atb.userIdx))
           ELSE 0
           END AS badgeUrl,
              CASE
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 1
               THEN u.dong
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 5
               THEN concat(u.gu, ' ', u.dong)
           WHEN
                   (6371 *
                    acos(cos(radians(us.lat)) * cos(radians(u.lat)) *
                         cos(radians(u.lon) - radians(us.lon))
                        + sin(radians(us.lat)) * sin(radians(u.lat)))) < 10
               THEN concat(u.si, ' ', u.gu, ' ', u.dong)
           END                                       AS address,
       concat('인증 ',u.certificationCount,'회') as certificationCount,
       tcs.sympathyIdx
from TownLifeCommentSympathy as tcs
         JOIN User as u ON tcs.userIdx = u.userIdx , User as us
where us.userIdx=? and tcs.commentIdx = ? and u.isdeleted='N';
";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$commentIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}




// 댓글 등록
function createComment($townLifeIdx,$comment,$photoUrl,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO TownLifeComment (userIdx, townLifeIdx,comment,photoUrl,parentIdx)
              VALUES (?,?,?,?,0);";

    // parentIdx 어케하지 ? if 조건 - 대댓글이면 원댓글 idx를 아니면 0
    // 댓글쓰기랑 대댓글쓰기 따로 ? 댓글쓰기는 parent 0 으로
    // 대댓글쓰기 commentidx가 있어야하고
    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$townLifeIdx,$comment,$photoUrl]);
    $commentIdx=$pdo->lastInsertId();
    $st = null;
    $pdo = null;
    return ['commentIdx'=>$commentIdx];
}



// 대댓글 등록
function createCommentComment( $townLifeIdx,$commentIdx,$comment,$photoUrl,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO TownLifeComment (userIdx, townLifeIdx,comment,parentIdx,photoUrl)
              VALUES (?,?,?,?,?);";

    // parentIdx 어케하지 ? if 조건 - 대댓글이면 원댓글 idx를 아니면 0
    // 댓글쓰기랑 대댓글쓰기 따로 ? 댓글쓰기는 parent 0 으로
    // 대댓글쓰기 commentidx가 있어야하고
    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$townLifeIdx,$comment,$commentIdx,$photoUrl]);
    $commentIdx=$pdo->lastInsertId();
    $st = null;
    $pdo = null;
    return  ['commentIdx'=>$commentIdx];
}

// 댓글삭제
function deleteComment($commentIdx,$userIdxInToken)
{

    $pdo = pdoSqlConnect();
    $queryComment = "UPDATE TownLifeComment SET isdeleted = 'Y' WHERE commentIdx=? and userIdx = ?;";
    $querySympathy = "UPDATE TownLifeCommentSympathy SET isdeleted = 'Y' WHERE commentIdx=?;";

    try {
        $st1 = $pdo->prepare($queryComment);
        $st2 = $pdo->prepare($querySympathy);


        $pdo->beginTransaction();

        $st1->execute([$commentIdx,$userIdxInToken]);
        $st2->execute([$commentIdx]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
            // If we got here our two data updates are not in the database
        }
        //throw $e;
        return $e->getMessage();
    }

    $st1 = null;
    $st2 = null;
    $pdo = null;

}

function isDeletedComment($commentIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select * from TownLifeComment where commentIdx = ? and isDeleted = 'Y') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$commentIdx]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);



}


function deleteContent($townLifeIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $queryContent = "UPDATE TownLife SET isdeleted = 'Y' WHERE townLifeIdx=? and userIdx=?;";
    $queryImg = "UPDATE TownLifePhoto SET isdeleted = 'Y' WHERE townLifeIdx=?;";
    $queryComment = "UPDATE TownLifeComment SET isdeleted = 'Y' WHERE townLifeIdx=?;";
    $querySympathy = "UPDATE TownLifeSympathy SET isdeleted = 'Y' WHERE townLifeIdx=?;";
    $queryCommentSympathy = "UPDATE TownLifeCommentSympathy SET isdeleted = 'Y' WHERE townLifeIdx=?;";

    try {
        $st1 = $pdo->prepare($queryContent);
        $st2 = $pdo->prepare($queryImg);
        $st3 = $pdo->prepare($queryComment);
        $st4 = $pdo->prepare($querySympathy);
        $st5 = $pdo->prepare($queryCommentSympathy);

        $pdo->beginTransaction();

        $st1->execute([$townLifeIdx,$userIdxInToken]);
        $st2->execute([$townLifeIdx]);
        $st3->execute([$townLifeIdx]);
        $st4->execute([$townLifeIdx]);
        $st5->execute([$townLifeIdx]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
            // If we got here our two data updates are not in the database
        }
        //throw $e;
        return $e->getMessage();
    }

    $st1 = null;
    $st2 = null;
    $st3 = null;
    $st4 = null;
    $st5 = null;
    $pdo = null;
    // return "성공";
}
// 글 삭제
function isDeletedContent($townLifeIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select * from TownLife where townLifeIdx = ? and isDeleted = 'Y') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$townLifeIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
