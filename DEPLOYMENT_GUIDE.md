# 🚀 DewScent 배포 가이드

배포 후 발생한 문제들을 해결하기 위한 완전한 가이드입니다.

## 📋 목차

1. [배포 전 준비사항](#배포-전-준비사항)
2. [배포 후 초기 설정](#배포-후-초기-설정)
3. [문제 해결](#문제-해결)
4. [관리자 페이지 접근](#관리자-페이지-접근)

---

## 🔧 배포 전 준비사항

### 1. 환경 변수 설정 (.env 파일)

서버에 `.env` 파일을 생성하고 다음 내용을 입력하세요:

```bash
# 환경 설정
APP_ENV=production

# 사이트 URL (실제 도메인으로 변경)
SITE_URL=https://your-domain.com

# 데이터베이스 설정
DB_HOST=localhost
DB_USER=your_db_user
DB_PASS=your_db_password
DB_NAME=dewscent

# 카카오 소셜 로그인
KAKAO_CLIENT_ID=your_kakao_client_id

# 네이버 소셜 로그인
NAVER_CLIENT_ID=your_naver_client_id
NAVER_CLIENT_SECRET=your_naver_client_secret

# 토스페이먼츠 설정 (결제 기능 사용 시)
TOSS_CLIENT_KEY=your_toss_client_key
TOSS_SECRET_KEY=your_toss_secret_key
```

`.env.example` 파일을 복사해서 사용할 수 있습니다:

```bash
cp .env.example .env
nano .env  # 실제 값으로 수정
```

### 2. 데이터베이스 생성

MySQL/MariaDB에 접속하여 데이터베이스를 생성하세요:

```sql
CREATE DATABASE dewscent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. 폴더 권한 설정

이미지 업로드 폴더에 쓰기 권한을 부여하세요:

```bash
chmod -R 755 public/images
chmod -R 755 uploads
```

---

## 🎯 배포 후 초기 설정

배포가 완료되면 **반드시** 다음 단계를 진행하세요:

### 방법 1: 자동 초기화 스크립트 실행 (권장)

브라우저에서 다음 URL에 접속하세요:

```
https://your-domain.com/init_deploy.php
```

이 스크립트는 자동으로:
- ✅ 모든 테이블 생성
- ✅ 기본 데이터 삽입 (배너, 감정, 상품 등)
- ✅ 관리자 계정 생성
- ✅ 데이터베이스 상태 확인

**주의:** 초기화가 완료되면 보안을 위해 `init_deploy.php` 파일을 삭제하세요:

```bash
rm init_deploy.php
```

### 방법 2: 수동 초기화

자동 초기화가 실패하면 다음 순서로 수동 실행하세요:

1. **테이블 생성:**
   ```
   https://your-domain.com/setup_database.php
   ```

2. **기본 데이터 삽입:**
   ```
   https://your-domain.com/insert_default_data.php
   ```

---

## 🔑 기본 관리자 계정

초기화 후 다음 계정으로 관리자 페이지에 로그인할 수 있습니다:

- **이메일:** `admin@dewscent.com`
- **비밀번호:** `admin123`

**⚠️ 보안 경고:** 로그인 후 즉시 비밀번호를 변경하세요!

---

## ❗ 문제 해결

### 1. 이미지가 표시되지 않는 경우

**원인:** 이미지 폴더가 없거나 권한 문제

**해결방법:**

```bash
# 이미지 폴더 생성
mkdir -p public/images/{products,banners,popups,temp}
mkdir -p uploads/images

# 권한 설정
chmod -R 755 public/images
chmod -R 755 uploads
```

### 2. 관리자 페이지에 접근할 수 없는 경우

**원인:** 관리자 계정이 생성되지 않음

**해결방법:**

브라우저에서 다음 URL 접속:
```
https://your-domain.com/init_deploy.php
```

또는 수동으로 관리자 계정 생성:
```
https://your-domain.com/insert_default_data.php
```

### 3. 배너/상품이 표시되지 않는 경우

**원인:** 기본 데이터가 삽입되지 않음

**해결방법:**

1. `init_deploy.php`를 실행하여 자동으로 데이터 삽입
2. 또는 관리자 페이지에서 수동으로 배너/상품 추가

### 4. DB 연결 오류

**원인:** `.env` 파일이 없거나 DB 정보가 잘못됨

**해결방법:**

1. `.env` 파일이 프로젝트 루트에 있는지 확인
2. DB 정보가 정확한지 확인:
   ```bash
   cat .env  # 환경변수 확인
   ```
3. DB 접속 테스트:
   ```
   https://your-domain.com/check_env.php
   ```

### 5. 테이블이 자동 생성되지 않는 경우

**해결방법:**

메인 페이지(`index.php`)에 접속하면 자동으로 테이블이 생성됩니다.
만약 생성되지 않으면:

```
https://your-domain.com/init_deploy.php
```

---

## 🎨 관리자 페이지 접근

### 1. 로그인

```
https://your-domain.com/admin/login.php
```

### 2. 대시보드

```
https://your-domain.com/admin/dashboard.php
```

### 3. 관리 가능한 항목

- 배너 관리
- 상품 관리
- 주문 관리
- 회원 관리
- 문의/리뷰 관리
- 쿠폰 관리
- 사이트 설정

---

## 📁 폴더 구조

```
dewscent/
├── .env                    # 환경변수 (절대 Git에 올리지 마세요!)
├── .env.example           # 환경변수 예시
├── init_deploy.php        # 배포 초기화 스크립트 (1회 실행 후 삭제)
├── admin/                 # 관리자 페이지
├── api/                   # REST API
├── includes/              # 공통 파일
│   ├── config.php         # 설정
│   ├── db.php            # DB 연결
│   └── db_setup.php      # 테이블 자동 생성
├── public/
│   ├── css/
│   ├── js/
│   └── images/           # 이미지 저장 폴더
│       ├── products/
│       ├── banners/
│       └── popups/
└── uploads/              # 사용자 업로드 파일
    └── images/
```

---

## ✅ 배포 체크리스트

배포 전:
- [ ] `.env` 파일 생성 및 설정
- [ ] 데이터베이스 생성
- [ ] 소셜 로그인 키 발급 (카카오, 네이버)
- [ ] 결제 키 발급 (토스페이먼츠)

배포 후:
- [ ] `init_deploy.php` 실행
- [ ] 관리자 계정 로그인 확인
- [ ] 관리자 비밀번호 변경
- [ ] 배너/상품 데이터 확인
- [ ] 이미지 업로드 테스트
- [ ] `init_deploy.php` 파일 삭제

---

## 🆘 도움이 필요하신가요?

1. **에러 로그 확인:**
   ```bash
   tail -f /var/log/apache2/error.log  # Apache
   tail -f /var/log/nginx/error.log    # Nginx
   ```

2. **PHP 오류 확인:**
   - `.env`에서 `APP_ENV=development`로 변경 (임시)
   - 브라우저에서 상세 오류 확인
   - 확인 후 다시 `APP_ENV=production`으로 변경

3. **DB 연결 테스트:**
   ```
   https://your-domain.com/check_env.php
   ```

---

## 🔒 보안 권장사항

1. **관리자 비밀번호 변경**
2. **init_deploy.php 삭제**
3. **불필요한 테스트 파일 삭제:**
   ```bash
   rm check_*.php
   rm debug_*.php
   rm create_*.php
   rm setup_database.php
   rm insert_default_data.php
   ```
4. **폴더 권한 최소화:**
   ```bash
   chmod 644 .env
   chmod 755 public/images
   chmod 755 uploads
   ```

---

## 🎉 완료!

이제 DewScent가 정상적으로 작동할 것입니다!

문제가 계속되면 GitHub Issues에 문의하세요.
