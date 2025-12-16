<?php
$inPages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$adminPath  = $inPages ? '../admin/index.php' : 'admin/index.php';
$signupPath = $inPages ? '../api/signup.php'  : 'api/signup.php';
?>
<!-- 웰컴 팝업 (향기 테스트) -->
<div class="popup-overlay" id="welcomePopup">
    <div class="popup">
        <div class="popup-header">
            <button class="popup-close" onclick="closePopup()">×</button>
        </div>
        <div class="popup-image">
            <h2>DewScent</h2>
            <p>오늘 기분에 어울리는 향기를 함께 찾아볼까요?</p>
        </div>
        <div class="popup-body">
            <p class="modal-subtitle">3분이면 끝나는 감정 기반 향기 테스트</p>
            <button class="form-btn primary" onclick="closePopup();openModal('testModal')">
                향기 테스트 시작하기
            </button>
            <button class="form-btn secondary" onclick="closePopup()">
                나중에 할래요
            </button>
            <button class="form-btn" onclick="hideWelcomePopupWeek()" style="margin-top:0.5rem;background:transparent;color:var(--light);border:1px solid var(--border);font-size:0.85rem;">
                일주일간 안보기
            </button>
        </div>
    </div>
</div>

<!-- 위시리스트 모달 -->
<div class="modal-overlay" id="wishlistModal">
    <div class="modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('wishlistModal')">×</button>
            <p class="modal-logo">위시리스트</p>
            <p class="modal-subtitle">찜해둔 제품을 확인해보세요</p>
        </div>
        <div class="modal-body">
            <div id="wishlistBody"></div>
        </div>
    </div>
</div>

<!-- 관리자 로그인 모달 -->
<div class="modal-overlay" id="adminLoginModal">
    <div class="modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('adminLoginModal')">×</button>
            <p class="modal-logo">관리자 로그인</p>
            <p class="modal-subtitle">관리자 전용 영역입니다</p>
        </div>
        <div class="modal-body">
            <form method="post" action="<?php echo $adminPath; ?>">
                <div class="form-group">
                    <label class="form-label">아이디</label>
                    <input type="text" name="username" class="form-input" placeholder="admin" required>
                </div>
                <div class="form-group">
                    <label class="form-label">비밀번호</label>
                    <input type="password" name="password" class="form-input" placeholder="비밀번호를 입력하세요" required>
                </div>
                <button type="submit" class="form-btn primary">로그인</button>
                <button type="button" class="form-btn secondary" onclick="closeModal('adminLoginModal')">닫기</button>
            </form>
        </div>
    </div>
</div>

<!-- 로그인 모달 -->
<div class="modal-overlay" id="loginModal">
    <div class="modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('loginModal')">×</button>
            <p class="modal-logo">DewScent</p>
            <p class="modal-subtitle">다시 만나서 반가워요</p>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">이메일</label>
                <input type="email" id="loginEmail" class="form-input" placeholder="you@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">비밀번호</label>
                <input type="password" id="loginPassword" class="form-input" placeholder="비밀번호를 입력하세요">
            </div>
            <button class="form-btn primary" onclick="login()">로그인</button>

            <div class="form-divider">
                <span>또는</span>
            </div>

            <div class="social-btns">
                <button class="social-btn">카카오로 로그인</button>
                <button class="social-btn">네이버로 로그인</button>
            </div>

            <div class="form-footer">
                <span>아직 DewScent가 처음이신가요? </span>
                <a href="#" onclick="closeModal('loginModal');openModal('signupModal')">회원가입 하기</a>
            </div>
        </div>
    </div>
</div>

<!-- 회원가입 모달 -->
<div class="modal-overlay" id="signupModal">
  <div class="modal">
    <div class="modal-header">
      <button class="modal-close" onclick="closeModal('signupModal')">×</button>
      <p class="modal-logo">DewScent</p>
      <p class="modal-subtitle">당신의 향기 여정을 시작해요</p>
    </div>

    <div class="modal-body">
      <form method="post" action="<?php echo $signupPath; ?>">
        <div class="form-group">
          <label class="form-label">이름</label>
          <input type="text" name="username" class="form-input" placeholder="이름을 입력하세요" required>

        </div>

        <div class="form-group">
          <label class="form-label">이메일</label>
          <input type="email" name="email" class="form-input" placeholder="you@example.com" required>
        </div>

        <div class="form-group">
          <label class="form-label">비밀번호</label>
          <input type="password" name="password" class="form-input" placeholder="8자 이상 입력" required>
        </div>

        <button type="submit" class="form-btn primary">회원가입</button>

        <div class="form-footer">
          <span>이미 계정이 있으신가요? </span>
          <a href="#" onclick="closeModal('signupModal');openModal('loginModal')">로그인 하기</a>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 마이페이지 모달 -->
<div class="modal-overlay" id="mypageModal">
    <div class="modal mypage-modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('mypageModal')">×</button>
            <p class="modal-logo">마이페이지</p>
            <p class="modal-subtitle">내 정보 및 주문 내역</p>
        </div>
        <div class="modal-body" id="mypageBody">
            <!-- JS로 사용자 정보가 렌더링됩니다 -->
        </div>
    </div>
</div>

<!-- 향기 테스트 모달 -->
<div class="modal-overlay" id="testModal">
    <div class="modal test-modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('testModal')">×</button>
            <p class="modal-logo">향기 테스트</p>
            <p class="modal-subtitle">몇 가지 질문으로 당신의 향기 타입을 찾아볼게요</p>
        </div>
        <div class="modal-body" id="testBody">
            <!-- JS에서 내용이 동적으로 채워짐 -->
        </div>
    </div>
</div>

<!-- 상품 상세 모달 -->
<div class="modal-overlay" id="productModal">
    <div class="modal product-modal">
        <div class="product-modal-image"></div>
        <div class="product-modal-content">
            <button class="product-modal-close" onclick="closeModal('productModal')">×</button>
            <p class="product-modal-brand">DewScent</p>
            <h3 class="product-modal-name" id="productModalName"></h3>
            <div class="product-modal-rating">
                <span class="stars">★★★★★</span>
                <span id="productModalRating"></span>
            </div>
            <p class="product-modal-price" id="productModalPrice"></p>
            <p class="product-modal-desc" id="productModalDesc"></p>

            <div class="product-options">
                <p class="option-label">용량 선택</p>
                <div class="option-btns" id="productSizeOptions">
                    <button class="option-btn selected" data-size="30">30ml</button>
                    <button class="option-btn" data-size="50">50ml</button>
                    <button class="option-btn" data-size="100">100ml</button>
                </div>
            </div>

            <div class="product-options">
                <p class="option-label">타입 선택</p>
                <div class="option-btns" id="productTypeOptions">
                    <button class="option-btn selected" data-type="perfume">향수</button>
                    <button class="option-btn" data-type="mist">바디미스트</button>
                    <button class="option-btn" data-type="diffuser">디퓨저</button>
                </div>
            </div>

            <div class="product-modal-actions">
                <button class="add-cart-btn" onclick="addToCart()">장바구니</button>
                <button class="buy-now-btn" onclick="buyNow()">바로 구매</button>
                <button class="wishlist-btn" onclick="toggleProductWishlist(this)">♡</button>
            </div>

            <div class="product-review-actions">
                <button class="review-view-btn" onclick="openReviewList()">리뷰 보기 <span id="reviewCountBadge"></span></button>
            </div>
        </div>
    </div>
</div>

<!-- 리뷰 목록 모달 -->
<div class="modal-overlay" id="reviewListModal">
    <div class="modal review-list-modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('reviewListModal')">×</button>
            <p class="modal-logo">리뷰</p>
            <p class="modal-subtitle" id="reviewListSubtitle">고객님들의 솔직한 후기</p>
        </div>
        <div class="modal-body">
            <div id="reviewListBody"></div>
        </div>
    </div>
</div>

<!-- 장바구니 모달 -->
<div class="modal-overlay" id="cartModal">
    <div class="modal cart-modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('cartModal')">×</button>
            <p class="modal-logo">장바구니</p>
            <p class="modal-subtitle">담아두신 향기들을 확인해보세요</p>
        </div>
        <div class="modal-body">
            <div id="cartBody"></div>
        </div>
    </div>
</div>

<!-- 리뷰 작성 모달 -->
<div class="modal-overlay" id="reviewModal">
    <div class="modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('reviewModal')">×</button>
            <p class="modal-logo">리뷰 작성</p>
            <p class="modal-subtitle">당신의 후기가 다른 사람에게 큰 도움이 돼요</p>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">별점을 선택해주세요</label>
                <div class="rating-select" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    <label class="rating-option">
                        <input type="radio" name="rating" value="5" style="display:none;">
                        <span class="rating-btn">★★★★★</span>
                    </label>
                    <label class="rating-option">
                        <input type="radio" name="rating" value="4" style="display:none;">
                        <span class="rating-btn">★★★★☆</span>
                    </label>
                    <label class="rating-option">
                        <input type="radio" name="rating" value="3" style="display:none;">
                        <span class="rating-btn">★★★☆☆</span>
                    </label>
                    <label class="rating-option">
                        <input type="radio" name="rating" value="2" style="display:none;">
                        <span class="rating-btn">★★☆☆☆</span>
                    </label>
                    <label class="rating-option">
                        <input type="radio" name="rating" value="1" style="display:none;">
                        <span class="rating-btn">★☆☆☆☆</span>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">리뷰 내용 (10자 이상)</label>
                <textarea class="form-input" id="reviewContent" rows="4" placeholder="사용해보신 느낌을 자유롭게 남겨주세요" style="resize:none"></textarea>
            </div>
            <button class="form-btn primary" onclick="submitReview()">리뷰 등록하기</button>
        </div>
    </div>
</div>

<!-- 결제(주문서) 모달 -->
<div class="modal-overlay" id="checkoutModal">
    <div class="modal checkout-modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('checkoutModal')">×</button>
            <p class="modal-logo">주문서 작성</p>
            <p class="modal-subtitle">입금 계좌 안내와 함께 주문을 완료합니다</p>
        </div>
        <div class="modal-body">
            <div class="checkout-section">
                <p class="checkout-section-title">주문자 정보</p>
                <div class="form-group">
                    <label class="form-label">이름</label>
                    <input type="text" class="form-input" placeholder="받으시는 분 이름">
                </div>
                <div class="form-group">
                    <label class="form-label">연락처</label>
                    <input type="text" class="form-input" placeholder="010-0000-0000">
                </div>
                <div class="form-group">
                    <label class="form-label">주소</label>
                    <input type="text" class="form-input" placeholder="배송 받으실 주소">
                </div>
            </div>

            <div class="checkout-section">
                <p class="checkout-section-title">결제 방법</p>
                <div class="payment-options">
                    <div class="payment-option selected">
                        <input type="radio" name="payment" value="bank" checked>
                        <div class="payment-option-info">
                            <h5>무통장 입금</h5>
                            <p>입금 확인 후 순차 발송됩니다.</p>
                        </div>
                    </div>
                    <div class="payment-option">
                        <input type="radio" name="payment" value="card">
                        <div class="payment-option-info">
                            <h5>카드 결제 (준비중)</h5>
                            <p>현재는 무통장 입금만 가능합니다.</p>
                        </div>
                    </div>
                </div>

                <div class="bank-info" id="bankInfo">
                    <p><strong>입금 계좌</strong></p>
                    <p>신한은행 110-123-456789</p>
                    <p>예금주: (주)듀센트</p>
                    <p style="font-size:.8rem;margin-top:.5rem;color:var(--light)">
                        주문 후 24시간 이내 입금이 확인되지 않으면 자동 취소될 수 있습니다.
                    </p>
                </div>
            </div>

            <div class="checkout-section">
                <p class="checkout-section-title">쿠폰</p>
                <div style="margin-bottom:1rem;">
                    <div style="display:flex;gap:.5rem;margin-bottom:.75rem;flex-wrap:wrap;">
                        <input type="text" id="couponCode" class="form-input" placeholder="쿠폰 코드를 입력하세요" style="flex:1;min-width:200px;padding:.75rem;font-size:.95rem;">
                        <button class="form-btn secondary" onclick="applyCouponCode()" style="padding:.75rem 1.5rem;white-space:nowrap;font-size:.9rem;min-width:80px;">적용</button>
                    </div>
                    <button class="form-btn ivory" onclick="openMypageTab('coupons');closeModal('checkoutModal');" style="width:100%;padding:.65rem;font-size:.9rem;margin-bottom:.5rem;">내 쿠폰 보기</button>
                </div>
                <div id="couponInfo" style="display:none;padding:1rem;background:linear-gradient(135deg,var(--sage-bg),var(--rose-lighter));border:1px solid var(--sage-lighter);border-radius:10px;margin-bottom:1rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div style="flex:1;">
                            <p style="color:var(--sage);font-weight:600;font-size:.95rem;margin-bottom:.25rem;" id="couponName"></p>
                            <p style="font-size:.8rem;color:var(--light);">쿠폰이 적용되었습니다</p>
                        </div>
                        <button onclick="removeCoupon()" style="background:var(--white);border:1px solid var(--border);border-radius:50%;color:var(--light);cursor:pointer;font-size:1.1rem;padding:0;width:28px;height:28px;display:flex;align-items:center;justify-content:center;transition:all 0.2s;" onmouseover="this.style.borderColor='var(--rose)';this.style.color='var(--rose)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--light)'">×</button>
                    </div>
                </div>
                <div id="myCouponsList" style="display:none;margin-top:.75rem;">
                    <p style="font-size:.85rem;color:var(--mid);margin-bottom:.75rem;font-weight:500;">사용 가능한 내 쿠폰</p>
                    <div id="availableCouponsList" style="display:flex;flex-direction:column;gap:.6rem;max-height:220px;overflow-y:auto;padding-right:.25rem;scrollbar-width:none;-ms-overflow-style:none;"></div>
                    <style>
                      #availableCouponsList::-webkit-scrollbar {
                        display: none;
                        width: 0;
                        height: 0;
                      }
                      #availableCouponsList::-webkit-scrollbar-track {
                        display: none;
                      }
                      #availableCouponsList::-webkit-scrollbar-thumb {
                        display: none;
                      }
                      #availableCouponsList::-webkit-scrollbar-button {
                        display: none;
                      }
                      #availableCouponsList::-webkit-scrollbar-corner {
                        display: none;
                      }
                    </style>
                </div>
            </div>

            <div class="checkout-section">
                <p class="checkout-section-title">결제 금액</p>
                <div class="cart-row">
                    <span>상품 금액</span>
                    <span id="checkoutSubtotal">₩0</span>
                </div>
                <div class="cart-row" id="couponDiscountRow" style="display:none;">
                    <span>할인 금액</span>
                    <span id="checkoutDiscount" style="color:var(--rose);">-₩0</span>
                </div>
                <div class="cart-row">
                    <span>배송비</span>
                    <span id="checkoutShipping">₩0</span>
                </div>
                <div class="cart-row total">
                    <span>총 결제금액</span>
                    <span id="checkoutTotal">₩0</span>
                </div>
            </div>

            <button class="form-btn primary" onclick="completeOrder()">주문 완료</button>
        </div>
    </div>
</div>

<!-- 주문 완료 모달 -->
<div class="modal-overlay" id="orderCompleteModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('orderCompleteModal')">×</button>
            <p class="modal-logo" style="color:var(--sage);">주문이 완료되었습니다!</p>
            <p class="modal-subtitle">주문 정보를 확인해주세요</p>
        </div>
        <div class="modal-body" id="orderCompleteBody">
            <!-- JS로 주문 정보가 렌더링됩니다 -->
        </div>
    </div>
</div>

<!-- 주문 상세 보기 모달 -->
<div class="modal-overlay" id="orderDetailModal">
    <div class="modal" style="max-width:700px;">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('orderDetailModal')">×</button>
            <p class="modal-logo">주문 상세</p>
            <p class="modal-subtitle" id="orderDetailSubtitle">주문번호: -</p>
        </div>
        <div class="modal-body" id="orderDetailBody">
            <!-- JS로 주문 상세 정보가 렌더링됩니다 -->
        </div>
    </div>
</div>

<!-- 문의하기 모달 -->
<div class="modal-overlay" id="inquiryModal">
    <div class="modal inquiry-modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('inquiryModal')">×</button>
            <p class="modal-logo">1:1 문의</p>
            <p class="modal-subtitle">궁금한 점을 남겨주시면 빠르게 답변드릴게요</p>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">문의 유형</label>
                <select class="form-input" id="inquiryType">
                    <option value="">선택해주세요</option>
                    <option value="shipping">배송 문의</option>
                    <option value="exchange">교환/환불</option>
                    <option value="product">상품 문의</option>
                    <option value="order">주문/결제 문의</option>
                    <option value="other">기타</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">주문번호 (선택)</label>
                <input type="text" class="form-input" id="inquiryOrderNo" placeholder="주문번호를 입력해주세요">
            </div>
            <div class="form-group">
                <label class="form-label">제목</label>
                <input type="text" class="form-input" id="inquiryTitle" placeholder="문의 제목을 입력해주세요">
            </div>
            <div class="form-group">
                <label class="form-label">문의 내용</label>
                <textarea class="form-input" id="inquiryContent" rows="5" placeholder="문의 내용을 자세히 적어주세요" style="resize:none"></textarea>
            </div>
            <div class="inquiry-notice">
                <p>• 영업일 기준 1~2일 내 답변드립니다.</p>
                <p>• 교환/환불은 수령 후 7일 이내 가능합니다.</p>
            </div>
            <button class="form-btn primary" onclick="submitInquiry()">문의 등록하기</button>
        </div>
    </div>
</div>

<!-- 반품/교환 신청 모달 -->
<div class="modal-overlay" id="returnExchangeModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('returnExchangeModal')">×</button>
            <p class="modal-logo">반품/교환 신청</p>
            <p class="modal-subtitle" id="returnExchangeSubtitle">주문번호: -</p>
        </div>
        <div class="modal-body" id="returnExchangeBody">
            <!-- JS로 반품/교환 신청 폼이 렌더링됩니다 -->
        </div>
    </div>
</div>

<!-- 내 문의 내역 모달 -->
<div class="modal-overlay" id="inquiryListModal">
    <div class="modal inquiry-list-modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal('inquiryListModal')">×</button>
            <p class="modal-logo">내 문의 내역</p>
            <p class="modal-subtitle">등록하신 문의와 답변을 확인하세요</p>
        </div>
        <div class="modal-body">
            <div id="inquiryListBody"></div>
            <button class="form-btn secondary" style="margin-top:1rem;" onclick="closeModal('inquiryListModal');openModal('inquiryModal')">새 문의하기</button>
        </div>
    </div>
</div>
