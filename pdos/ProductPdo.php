<?php





// 중고거래 물품 조회
function getProduct($userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "
SELECT p.productIdx,
       p.title,
       CASE
           WHEN exists(select * from ProductPhoto as pp WHERE sequence = 1 and p.productIdx = pp.productIdx)
           THEN (select productPhotoUrl from ProductPhoto as pp WHERE sequence = 1 and p.productIdx = pp.productIdx)
           ELSE 'http://default'
       END AS productPhotoUrl,
       CASE
           WHEN p.up = 'Y'
               THEN
               CASE
                   WHEN TIMESTAMPDIFF(SECOND, p.updatedAt, current_timestamp()) < 60
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(SECOND, p.updatedAt, current_timestamp()) as char), '초 전')
                   WHEN TIMESTAMPDIFF(MINUTE, p.updatedAt, current_timestamp()) < 60
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(MINUTE, p.updatedAt, current_timestamp()) as char), '분 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) < 24
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) as char), '시간 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) = 24
                       THEN '끌올 어제'
                   WHEN TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) < 7
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) as char), '일 전')
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 7) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 14
                       THEN '끌올 지난 주'
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 14) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 21
                       THEN '끌올 2주 전'
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 21) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 28
                       THEN '끌올 3주 전'
                   ELSE '미정'
                   END
           WHEN p.up = 'N'
               THEN
               CASE
                   WHEN TIMESTAMPDIFF(SECOND, p.createdAt, current_timestamp()) < 60
                       THEN concat(cast(TIMESTAMPDIFF(SECOND, p.createdAt, current_timestamp()) as char), '초 전')
                   WHEN TIMESTAMPDIFF(MINUTE, p.createdAt, current_timestamp()) < 60
                       THEN concat(cast(TIMESTAMPDIFF(MINUTE, p.createdAt, current_timestamp()) as char), '분 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) < 24
                       THEN concat(cast(TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) as char), '시간 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) = 24
                       THEN '어제'
                   WHEN TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) < 7
                       THEN concat(cast(TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) as char), '일 전')
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 7) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 14
                       THEN '지난 주'
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 14) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 21
                       THEN '2주 전'
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 21) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 28
                       THEN '3주 전'
                   ELSE '미정'
                   END
           END                                       AS time,
       concat(cast(FORMAT(p.price, 0) as char), '원') AS price,
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
           END                                                                         AS address,
       (select count(*) from ProductInterest as pi where p.productIdx = pi.productIdx) as interestCount,
       (select count(*) from ProductChatting as pc where p.productIdx = pc.productIdx) as chattingCount
FROM Product AS p JOIN User AS u ON u.userIdx = p.userIdx, User as us
WHERE us.userIdx= ? and p.isDeleted != 'Y' and p.state!=3 and u.isDeleted='N'
      and (6371 *acos(cos(radians(us.lat)) * cos(radians(u.lat)) * cos(radians(u.lon) - radians(us.lon))
           + sin(radians(us.lat)) * sin(radians(u.lat)))) < (select townRange from User where userIdx=?)
ORDER BY CASE
             WHEN p.up = 'Y'
                 THEN p.updatedAt
             WHEN p.up = 'N'
                 THEN p.createdAt
             END DESC;";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}



// 특정키워드,카테고리제거 물품 조회
function getKeywordCategoryProducts($keyword,$excludedCategory,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "
SELECT p.productIdx,
       p.title,
       CASE
           WHEN exists(select * from ProductPhoto as pp WHERE sequence = 1 and p.productIdx = pp.productIdx)
           THEN (select productPhotoUrl from ProductPhoto as pp WHERE sequence = 1 and p.productIdx = pp.productIdx)
           ELSE 'http://default'
       END AS productPhotoUrl,
       CASE
           WHEN p.up = 'Y'
               THEN
               CASE
                   WHEN TIMESTAMPDIFF(SECOND, p.updatedAt, current_timestamp()) < 60
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(SECOND, p.updatedAt, current_timestamp()) as char), '초 전')
                   WHEN TIMESTAMPDIFF(MINUTE, p.updatedAt, current_timestamp()) < 60
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(MINUTE, p.updatedAt, current_timestamp()) as char), '분 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) < 24
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) as char), '시간 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) = 24
                       THEN '끌올 어제'
                   WHEN TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) < 7
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) as char), '일 전')
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 7) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 14
                       THEN '끌올 지난 주'
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 14) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 21
                       THEN '끌올 2주 전'
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 21) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 28
                       THEN '끌올 3주 전'
                   ELSE '미정'
                   END
           WHEN p.up = 'N'
               THEN
               CASE
                   WHEN TIMESTAMPDIFF(SECOND, p.createdAt, current_timestamp()) < 60
                       THEN concat(cast(TIMESTAMPDIFF(SECOND, p.createdAt, current_timestamp()) as char), '초 전')
                   WHEN TIMESTAMPDIFF(MINUTE, p.createdAt, current_timestamp()) < 60
                       THEN concat(cast(TIMESTAMPDIFF(MINUTE, p.createdAt, current_timestamp()) as char), '분 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) < 24
                       THEN concat(cast(TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) as char), '시간 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) = 24
                       THEN '어제'
                   WHEN TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) < 7
                       THEN concat(cast(TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) as char), '일 전')
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 7) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 14
                       THEN '지난 주'
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 14) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 21
                       THEN '2주 전'
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 21) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 28
                       THEN '3주 전'
                   ELSE '미정'
                   END
           END                                       AS time,
       concat(cast(FORMAT(p.price, 0) as char), '원') AS price,
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
           END                                                                         AS address,
       (select count(*) from ProductInterest as pi where p.productIdx = pi.productIdx) as interestCount,
       (select count(*) from ProductChatting as pc where p.productIdx = pc.productIdx) as chattingCount
FROM Product AS p JOIN User AS u ON u.userIdx = p.userIdx, User as us
WHERE us.userIdx= ? and p.isDeleted != 'Y' and p.state!=3 and p.title like concat('%',$keyword,'%')
      and p.categoryIdx not in  (".implode(',',$excludedCategory).") and u.isDeleted='N'
      and (6371 *acos(cos(radians(us.lat)) * cos(radians(u.lat)) * cos(radians(u.lon) - radians(us.lon))
           + sin(radians(us.lat)) * sin(radians(u.lat)))) < (select townRange from User where userIdx=?)
ORDER BY CASE
             WHEN p.up = 'Y'
                 THEN p.updatedAt
             WHEN p.up = 'N'
                 THEN p.createdAt
             END DESC;
";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 특정 검색어로 물품 조회
function getKeywordProducts($keyword,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "
SELECT p.productIdx,
       p.title,
       CASE
           WHEN exists(select * from ProductPhoto as pp WHERE sequence = 1 and p.productIdx = pp.productIdx)
           THEN (select productPhotoUrl from ProductPhoto as pp WHERE sequence = 1 and p.productIdx = pp.productIdx)
           ELSE 'http://default'
       END AS productPhotoUrl,
       CASE
           WHEN p.up = 'Y'
               THEN
               CASE
                   WHEN TIMESTAMPDIFF(SECOND, p.updatedAt, current_timestamp()) < 60
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(SECOND, p.updatedAt, current_timestamp()) as char), '초 전')
                   WHEN TIMESTAMPDIFF(MINUTE, p.updatedAt, current_timestamp()) < 60
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(MINUTE, p.updatedAt, current_timestamp()) as char), '분 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) < 24
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) as char), '시간 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) = 24
                       THEN '끌올 어제'
                   WHEN TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) < 7
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) as char), '일 전')
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 7) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 14
                       THEN '끌올 지난 주'
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 14) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 21
                       THEN '끌올 2주 전'
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 21) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 28
                       THEN '끌올 3주 전'
                   ELSE '미정'
                   END
           WHEN p.up = 'N'
               THEN
               CASE
                   WHEN TIMESTAMPDIFF(SECOND, p.createdAt, current_timestamp()) < 60
                       THEN concat(cast(TIMESTAMPDIFF(SECOND, p.createdAt, current_timestamp()) as char), '초 전')
                   WHEN TIMESTAMPDIFF(MINUTE, p.createdAt, current_timestamp()) < 60
                       THEN concat(cast(TIMESTAMPDIFF(MINUTE, p.createdAt, current_timestamp()) as char), '분 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) < 24
                       THEN concat(cast(TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) as char), '시간 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) = 24
                       THEN '어제'
                   WHEN TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) < 7
                       THEN concat(cast(TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) as char), '일 전')
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 7) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 14
                       THEN '지난 주'
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 14) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 21
                       THEN '2주 전'
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 21) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 28
                       THEN '3주 전'
                   ELSE '미정'
                   END
           END                                       AS time,
       concat(cast(FORMAT(p.price, 0) as char), '원') AS price,
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
           END                                                                         AS address,
       (select count(*) from ProductInterest as pi where p.productIdx = pi.productIdx) as interestCount,
       (select count(*) from ProductChatting as pc where p.productIdx = pc.productIdx) as chattingCount
FROM Product AS p JOIN User AS u ON u.userIdx = p.userIdx, User as us
WHERE us.userIdx= ? and p.isDeleted != 'Y' and p.state!=3 and p.title like concat('%',?,'%') and u.isDeleted='N'
      and (6371 *acos(cos(radians(us.lat)) * cos(radians(u.lat)) * cos(radians(u.lon) - radians(us.lon))
           + sin(radians(us.lat)) * sin(radians(u.lat)))) < (select townRange from User where userIdx=?)
ORDER BY CASE
             WHEN p.up = 'Y'
                 THEN p.updatedAt
             WHEN p.up = 'N'
                 THEN p.createdAt
             END DESC;
";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$keyword,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 특정 카테고리 제외한 물품 조회
function getCategoryProducts($excludedCategory,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "
SELECT p.productIdx,
       p.title,
       CASE
           WHEN exists(select * from ProductPhoto as pp WHERE sequence = 1 and p.productIdx = pp.productIdx)
           THEN (select productPhotoUrl from ProductPhoto as pp WHERE sequence = 1 and p.productIdx = pp.productIdx)
           ELSE 'http://default'
       END AS productPhotoUrl,
       CASE
           WHEN p.up = 'Y'
               THEN
               CASE
                   WHEN TIMESTAMPDIFF(SECOND, p.updatedAt, current_timestamp()) < 60
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(SECOND, p.updatedAt, current_timestamp()) as char), '초 전')
                   WHEN TIMESTAMPDIFF(MINUTE, p.updatedAt, current_timestamp()) < 60
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(MINUTE, p.updatedAt, current_timestamp()) as char), '분 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) < 24
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) as char), '시간 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) = 24
                       THEN '끌올 어제'
                   WHEN TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) < 7
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) as char), '일 전')
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 7) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 14
                       THEN '끌올 지난 주'
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 14) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 21
                       THEN '끌올 2주 전'
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 21) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 28
                       THEN '끌올 3주 전'
                   ELSE '미정'
                   END
           WHEN p.up = 'N'
               THEN
               CASE
                   WHEN TIMESTAMPDIFF(SECOND, p.createdAt, current_timestamp()) < 60
                       THEN concat(cast(TIMESTAMPDIFF(SECOND, p.createdAt, current_timestamp()) as char), '초 전')
                   WHEN TIMESTAMPDIFF(MINUTE, p.createdAt, current_timestamp()) < 60
                       THEN concat(cast(TIMESTAMPDIFF(MINUTE, p.createdAt, current_timestamp()) as char), '분 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) < 24
                       THEN concat(cast(TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) as char), '시간 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) = 24
                       THEN '어제'
                   WHEN TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) < 7
                       THEN concat(cast(TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) as char), '일 전')
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 7) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 14
                       THEN '지난 주'
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 14) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 21
                       THEN '2주 전'
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 21) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 28
                       THEN '3주 전'
                   ELSE '미정'
                   END
           END                                       AS time,
       concat(cast(FORMAT(p.price, 0) as char), '원') AS price,
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
           END                                                                         AS address,
       (select count(*) from ProductInterest as pi where p.productIdx = pi.productIdx) as interestCount,
       (select count(*) from ProductChatting as pc where p.productIdx = pc.productIdx) as chattingCount
FROM Product AS p JOIN User AS u ON u.userIdx = p.userIdx, User as us
WHERE us.userIdx= ? and p.isDeleted != 'Y' and p.state!=3 and p.categoryIdx not in  (".implode(',',$excludedCategory).") and u.isDeleted='N'
      and (6371 *acos(cos(radians(us.lat)) * cos(radians(u.lat)) * cos(radians(u.lon) - radians(us.lon))
           + sin(radians(us.lat)) * sin(radians(u.lat)))) < (select townRange from User where userIdx=?)
ORDER BY CASE
             WHEN p.up = 'Y'
                 THEN p.updatedAt
             WHEN p.up = 'N'
                 THEN p.createdAt
             END DESC;";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 특정 검색어 결과 있는지
function isValidKeyword($keyword)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select *
from Product as p
join User as u on u.userIdx=p.userIdx
where p.isDeleted !='Y' and p.title like concat('%',?,'%') and p.state!=3 and u.isDeleted='N') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$keyword]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
// 특정 카테고리 제외한 결과 있는지
function isValidCategory($excludedCategory)
{
    $pdo = pdoSqlConnect();
    $query = "
select EXISTS(
    select *
    from Product  as p
    join User as u on u.userIdx=p.userIdx
    where p.categoryIdx not in (2)
      and p.isDeleted !='Y' and p.state!=3  and u.isDeleted='N' ) exist;
";

    $st = $pdo->prepare($query);
    $st->execute([$excludedCategory]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}




//중고물품 세부 조회
function getProductDetail($productIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "
SELECT p.productIdx,
       CASE
           WHEN u.profilePhotoUrl IS NULL
               THEN 'http://default'
           ELSE u.profilePhotoUrl
           END AS profilePhotoUrl,
       CASE
           WHEN exists((select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where p.userIdx=atb.userIdx)))
           THEN (select abc.badgeUrl from ActivityBadgeCategory as abc where abc.badgeIdx
                    =(select atb.topBadgeIdx from ActivityTopBadge as atb where p.userIdx=atb.userIdx))
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
           END                                       AS address,
       concat(cast(u.mannerTemperature as char), '°C') AS mannerTemperature,
       (select pc.categoryName from ProductCategory as pc where p.categoryIdx=pc.categoryIdx) as categoryName,
       p.title,
       CASE
           WHEN p.up = 'Y'
               THEN
               CASE
                   WHEN TIMESTAMPDIFF(SECOND, p.updatedAt, current_timestamp()) < 60
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(SECOND, p.updatedAt, current_timestamp()) as char), '초 전')
                   WHEN TIMESTAMPDIFF(MINUTE, p.updatedAt, current_timestamp()) < 60
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(MINUTE, p.updatedAt, current_timestamp()) as char), '분 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) < 24
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) as char), '시간 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.updatedAt, current_timestamp()) = 24
                       THEN '끌올 어제'
                   WHEN TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) < 7
                       THEN concat('끌올 ', cast(TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) as char), '일 전')
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 7) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 14
                       THEN '끌올 지난 주'
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 14) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 21
                       THEN '끌올 2주 전'
                   WHEN (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp()) >= 21) and
                        (TIMESTAMPDIFF(DAY, p.updatedAt, current_timestamp())) < 28
                       THEN '끌올 3주 전'
                   ELSE '미정'
                   END
           WHEN p.up = 'N'
               THEN
               CASE
                   WHEN TIMESTAMPDIFF(SECOND, p.createdAt, current_timestamp()) < 60
                       THEN concat(cast(TIMESTAMPDIFF(SECOND, p.createdAt, current_timestamp()) as char), '초 전')
                   WHEN TIMESTAMPDIFF(MINUTE, p.createdAt, current_timestamp()) < 60
                       THEN concat(cast(TIMESTAMPDIFF(MINUTE, p.createdAt, current_timestamp()) as char), '분 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) < 24
                       THEN concat(cast(TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) as char), '시간 전')
                   WHEN TIMESTAMPDIFF(HOUR, p.createdAt, current_timestamp()) = 24
                       THEN '어제'
                   WHEN TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) < 7
                       THEN concat(cast(TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) as char), '일 전')
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 7) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 14
                       THEN '지난 주'
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 14) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 21
                       THEN '2주 전'
                   WHEN (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp()) >= 21) and
                        (TIMESTAMPDIFF(DAY, p.createdAt, current_timestamp())) < 28
                       THEN '3주 전'
                   ELSE '미정'
                   END
           END                                       AS time,
       p.productDetail,
       concat(cast(FORMAT(p.price, 0) as char), '원')    AS price,
       (select count(pi.productIdx) from ProductInterest as pi where p.productIdx = pi.productIdx) as interestCount,
       (select count(pct.productIdx) from ProductChatting as pct where p.productIdx = pct.productIdx) as chattingCount,
       (select count(pck.productIdx) from ProductCheck as pck where p.productIdx = pck.productIdx) as checkCount,
       CASE
           WHEN exists(select * from ProductInterest as pi where p.productIdx = ? and pi.userIdx = ?)
               THEN 'Y'
           ELSE 'N'
        END AS isHart,
        CASE
            WHEN priceDeal ='Y'
                THEN '가격 제안하기'
                ELSE '가격제안불가'
            END AS priceDeal
FROM User AS u
         INNER JOIN Product AS p ON u.userIdx = p.userIdx,User AS us
WHERE  us.userIdx=? and p.productIdx=? and p.isDeleted !='Y' and p.state!=3 and u.isDeleted='N';";

    $st = $pdo->prepare($query);
    $st->execute([$productIdx,$userIdxInToken,$userIdxInToken,$productIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

// 중고물품 세부내용 이미지 조회
function getProductImg($productIdx)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT pp.productPhotoUrl
FROM ProductPhoto as pp
JOIN Product as p ON p.productIdx=pp.productIdx
JOIN User as u ON u.userIdx=p.userIdx
WHERE  pp.isDeleted != 'Y' and u.isDeleted='N' and pp.productIdx=?;";
    $st = $pdo->prepare($query);
    $st->execute([$productIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function isValidProduct($productIdx)
{
    $pdo = pdoSqlConnect();
    $query = "
select EXISTS(
select p.productIdx
from Product as p
join User as u on u.userIdx=p.userIdx
where p.productIdx = ? and p.isDeleted !='Y' and p.state!=3 and u.isDeleted='N') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$productIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}



function createProduct($title,$price,$productDetail,$categoryIdx,$imgList,$priceDeal,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $queryContent = "INSERT INTO Product (userIdx,title,price,productDetail,categoryIdx,priceDeal) VALUES (?,?,?,?,?,if(isnull(?),'N',?));";
    $queryImg = "INSERT INTO ProductPhoto (productIdx,productPhotoUrl,sequence) VALUES (?,?,?);";

    try {

        $st1 = $pdo->prepare($queryContent);
        $st2 = $pdo->prepare($queryImg);

        $pdo->beginTransaction();

        $st1->execute([$userIdxInToken,$title,$price,$productDetail,$categoryIdx,$priceDeal,$priceDeal]);
        $productIdx = $pdo->lastInsertId();
        $sequence=1;
        foreach( $imgList as $img)
        {
            $st2->execute([$productIdx,$img,$sequence++]);
        }

        $pdo->commit();
    }
    catch (PDOException $e) {
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
    return $productIdx;


}



function isValidToken($userIdxInToken,$productIdx)
{
    $pdo = pdoSqlConnect();
    $query = "
    select EXISTS(
select p.productIdx
from Product as p
join User as u on u.userIdx=p.userIdx
where p.userIdx = ? and p.productIdx = ? and p.isDeleted !='Y' and p.state!=3 and u.isDeleted='N')exist;";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$productIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
// 물품 수정
function changeProduct($productIdx,$title,$price,$productDetail,$categoryIdx,$imgList,$priceDeal)
{
    $pdo = pdoSqlConnect();
    $queryContent = "UPDATE Product SET title=if(isnull(?),title,?), categoryIdx=if(isnull(?),categoryIdx,?),price=if(isnull(?),price,?),
                   productDetail=if(isnull(?),productDetail,?),priceDeal=if(isnull(?),priceDeal,?) where productIdx=?;";
    $queryDeleteImg = "delete from ProductPhoto where productIdx=?;";
    $queryInsertImg = "insert into ProductPhoto (productIdx,productPhotoUrl,sequence) values (?,?,?);";

    // if 이미지가 널이면 인서트만
    try {

        $st1 = $pdo->prepare($queryContent);
        if (!empty($imgList)){
            $st2 = $pdo->prepare($queryDeleteImg);
        }

        $st3 = $pdo->prepare($queryInsertImg);

        $pdo->beginTransaction();

        $st1->execute([$title,$title,$categoryIdx,$categoryIdx,$price,$price,$productDetail,$productDetail,$priceDeal,$priceDeal,$productIdx]);
        if (!empty($imgList)){
            $st2->execute([$productIdx]);
        }

        $sequence=1;
        foreach( $imgList as $img)
        {
            $st3->execute([$productIdx,$img,$sequence++]);
        }

        $pdo->commit();
    }
    catch (PDOException $e) {
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
}


function isValidChangeProduct($userIdxInToken,$productIdx)
{
    $pdo = pdoSqlConnect();
    $query = "
    select EXISTS(
select p.productIdx
from Product as p
join User as u on u.userIdx=p.userIdx
where p.userIdx = ? and p.productIdx = ? and p.isDeleted !='Y' and p.state!=3 and u.isDeleted='N')exist;";

    $st = $pdo->prepare($query);
    $st->execute([$userIdxInToken,$productIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
function isProductCategory($categoryIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select categoryIdx from ProductCategory where categoryIdx = ?) exist;";

    $st = $pdo->prepare($query);
    $st->execute([$categoryIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}

function upProduct($productIdx)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Product SET up = 'Y' WHERE productIdx=?  and isDeleted != 'Y' and state!=3;";

    $st = $pdo->prepare($query);
    $st->execute([$productIdx]);

    $st = null;
    $pdo = null;
    //return $res[0];
    //return "물품 끌어올리기 성공";
}


function isUpY($productIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select * from Product where productIdx = ? and up = 'Y' and isDeleted != 'Y' and state!=3) exist;";

    $st = $pdo->prepare($query);
    $st->execute([$productIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}

function interestProduct($productIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO ProductInterest (productIdx,userIdx) VALUES (?,?);";

    $st = $pdo->prepare($query);
    $st->execute([$productIdx,$userIdxInToken]);

    $st = null;
    $pdo = null;

    return ['productIdx'=>$productIdx,'useridx'=>$userIdxInToken];
}

function deleteInterest($productIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "delete from ProductInterest where productIdx=? and userIdx=?";

    $st = $pdo->prepare($query);
    $st->execute([$productIdx,$userIdxInToken]);

    $st = null;
    $pdo = null;

    return "성공";
}

function isInterest($productIdx,$userIdxInToken)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select productIdx from ProductInterest where productIdx = ? and userIdx=?) exist;";

    $st = $pdo->prepare($query);
    $st->execute([$productIdx,$userIdxInToken]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}


function deleteProduct($productIdx)
{
    $pdo = pdoSqlConnect();
    $queryContent = "UPDATE Product SET isdeleted = 'Y' WHERE productIdx=?;";
    $queryImg = "UPDATE ProductPhoto SET isdeleted = 'Y' WHERE productIdx=?;";
    $queryInterest = "UPDATE ProductInterest SET isdeleted = 'Y' WHERE productIdx=?;";

    try {
        $st1 = $pdo->prepare($queryContent);
        $st2 = $pdo->prepare($queryImg);
        $st3 = $pdo->prepare($queryInterest);

        $pdo->beginTransaction();

        $st1->execute([$productIdx]);
        $st2->execute([$productIdx]);
        $st3->execute([$productIdx]);

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
    //return "성공";


}


function isDeletedY($productIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select * from Product where productIdx = ? and isDeleted = 'Y'  and state!=3) exist;";

    $st = $pdo->prepare($query);
    $st->execute([$productIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}

