<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "주문 조회 | DewScent";
$currentPage = basename($_SERVER['PHP_SELF']);
$inPages = true;
$basePrefix = '../';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Noto+Sans+KR:wght@200;300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css?v=7">
    <script>
        window.DS_BASE_URL = "<?php echo rtrim(SITE_URL, '/'); ?>";
    </script>
    <script src="../public/js/api.js?v=4"></script>
    <script src="../public/js/main.js?v=5"></script>
<style>
.order-lookup-container {
    max-width: 600px;
    margin: 3rem auto;
    padding: 2rem;
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}
.order-lookup-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    color: var(--sage);
    text-align: center;
    margin-bottom: 1rem;
}
.order-lookup-subtitle {
    text-align: center;
    color: var(--light);
    font-size: 0.9rem;
    margin-bottom: 2rem;
}
.form-group {
    margin-bottom: 1.5rem;
}
.form-label {
    display: block;
    font-size: 0.9rem;
    color: var(--mid);
    margin-bottom: 0.5rem;
    font-weight: 500;
}
.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.95rem;
    transition: border-color 0.3s;
}
.form-input:focus {
    outline: none;
    border-color: var(--sage);
}
.form-btn {
    width: 100%;
    padding: 0.9rem;
    background: var(--sage);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
}
.form-btn:hover {
    background: var(--sage-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(95, 113, 97, 0.3);
}
.order-result {
    margin-top: 2rem;
    padding: 2rem;
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    display: none;
}
.order-result.show {
    display: block;
}
.order-result.error {
    background: var(--sage-bg);
    border: 1px solid var(--sage-lighter);
    border-left: 4px solid var(--sage);
}
.order-result.error .order-result-title {
    color: var(--sage);
}
.order-result-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--sage);
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--sage-lighter);
}
.order-detail-section {
    background: var(--white);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.order-info-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border);
}
.order-info-item:last-child {
    border-bottom: none;
}
.order-info-label {
    color: var(--mid);
    font-size: 0.9rem;
    font-weight: 500;
    min-width: 100px;
}
.order-info-value {
    color: var(--dark);
    font-weight: 500;
    text-align: right;
    flex: 1;
}
.order-items {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--sage-lighter);
}
.order-item {
    display: flex;
    gap: 1.25rem;
    padding: 1.25rem;
    background: var(--sage-bg);
    border-radius: 12px;
    margin-bottom: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
}
.order-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(95, 113, 97, 0.15);
}
.order-item-image {
    width: 100px;
    height: 100px;
    border-radius: 12px;
    object-fit: cover;
    background: var(--sage-lighter);
    flex-shrink: 0;
    border: 1px solid var(--border);
}
.order-item-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.order-item-name {
    font-weight: 600;
    font-size: 1rem;
    color: var(--dark);
    margin-bottom: 0.5rem;
    line-height: 1.4;
}
.order-item-details {
    font-size: 0.9rem;
    color: var(--mid);
    margin-bottom: 0.75rem;
}
.order-item-price {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--sage);
    margin-top: auto;
}
.status-badge {
    display: inline-block;
    padding: 0.3rem 0.8rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 500;
}
.status-badge.pending {
    background: var(--border);
    color: var(--mid);
}
.status-badge.paid {
    background: var(--sage-lighter);
    color: var(--sage);
}
.status-badge.preparing {
    background: #c9b896;
    color: #333;
}
.status-badge.shipping {
    background: #6b8cce;
    color: #fff;
}
.status-badge.delivered {
    background: var(--sage);
    color: #fff;
}
.status-badge.cancelled {
    background: var(--light);
    color: #fff;
}
.order-list-item {
    background: var(--white);
    padding: 1.25rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: transform 0.2s, box-shadow 0.2s;
}
.order-list-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.order-list-info {
    flex: 1;
}
.order-list-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}
.order-list-number {
    font-weight: 600;
    color: var(--dark);
    font-size: 1rem;
}
.order-list-date {
    color: var(--mid);
    font-size: 0.85rem;
}
.order-list-footer {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 0.5rem;
}
.order-list-total {
    font-weight: 700;
    color: var(--sage);
    font-size: 1.1rem;
}
.order-detail-btn {
    padding: 0.6rem 1.5rem;
    background: var(--sage);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
}
.order-detail-btn:hover {
    background: var(--sage-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(95, 113, 97, 0.3);
}
.order-detail-modal {
    display: none;
}
.order-detail-modal.active {
    display: block;
}
</style>

<?php 
// 주문 조회 페이지에서는 auth_state.php가 localStorage를 덮어쓰지 않도록
// 세션에 user_id가 있어도 주문 조회 페이지에서는 무시
$isOrderLookupPage = true;
include __DIR__ . '/../includes/header.php'; 
include __DIR__ . '/../includes/sidebar.php'; 
?>
<main id="main" class="visible">
<div class="order-lookup-container">
    <h1 class="order-lookup-title">주문 조회</h1>
    <p class="order-lookup-subtitle">주문번호와 이메일 또는 전화번호를 입력하세요</p>
    
    <form id="orderLookupForm">
        <div class="form-group">
            <label class="form-label">주문번호 <span style="color:var(--light);font-size:.8rem;">(선택사항)</span></label>
            <input type="text" class="form-input" id="orderNumber" placeholder="예: ORD-20251218-123456">
            <p style="font-size:.8rem;color:var(--light);margin-top:.5rem;">주문번호를 입력하면 더 빠르게 조회할 수 있습니다.</p>
        </div>
        
        <div class="form-group">
            <label class="form-label">이메일 또는 전화번호 <span style="color:var(--rose);font-size:.8rem;">*</span></label>
            <input type="text" class="form-input" id="guestInfo" placeholder="주문 시 입력한 이메일 또는 전화번호를 입력하세요" required>
            <p style="font-size:.8rem;color:var(--light);margin-top:.5rem;">비회원 주문의 경우 주문 시 입력한 이메일 또는 전화번호를 입력해주세요. 취소된 주문도 조회됩니다.</p>
        </div>
        
        <button type="submit" class="form-btn">주문 조회</button>
    </form>
    
    <div id="orderResult" class="order-result">    </div>
</div>

<!-- 주문 상세 모달 -->
<div class="modal-overlay" id="orderDetailModal">
    <div class="modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeOrderDetailModal()">×</button>
            <p class="modal-logo">주문 상세 정보</p>
            <p class="modal-subtitle">주문 내역을 확인하세요</p>
        </div>
        <div class="modal-body" id="orderDetailContent">
        </div>
        <div class="modal-footer" style="padding:1.5rem;border-top:1px solid var(--border);display:flex;gap:0.75rem;justify-content:flex-end;">
            <button class="form-btn secondary" onclick="closeOrderDetailModal()">닫기</button>
            <button class="form-btn" onclick="goToMainPage()" style="background:var(--sage);color:#fff;">메인으로</button>
        </div>
    </div>
</div>
</main>

<script>
// 비회원 주문 조회 시 사용자 정보가 localStorage에 저장되지 않도록 보장
// main.js에서 이미 USER_KEY가 선언되어 있으므로 여기서는 사용만 함
const clearGuestUser = () => {
    if (typeof getCurrentUser === 'function' && !getCurrentUser()) {
        // main.js의 USER_KEY 상수 사용 (이미 선언되어 있음)
        localStorage.removeItem("ds_current_user");
    }
};

// 페이지 로드 시 비회원인 경우 사용자 정보 제거
// 주문 조회 페이지에서는 무조건 localStorage 정리 (비회원 조회 페이지이므로)
localStorage.removeItem("ds_current_user");
// 세션 스토리지에 플래그 설정 (메인 페이지에서 감지용)
sessionStorage.setItem('from_order_lookup', 'true');

document.getElementById('orderLookupForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const orderNumber = document.getElementById('orderNumber').value.trim();
    const guestInfo = document.getElementById('guestInfo').value.trim();
    const resultDiv = document.getElementById('orderResult');
    
    if (!guestInfo) {
        alert('이메일 또는 전화번호를 입력해주세요.');
        return;
    }
    
    // 비회원 조회 시작 전 사용자 정보 제거
    clearGuestUser();
    
    // 주문번호 필드에 이메일이 잘못 입력된 경우 처리
    // 주문번호는 ORD-로 시작하거나 숫자로만 구성되어야 함
    let actualOrderNumber = orderNumber;
    let actualGuestInfo = guestInfo;
    
    // 주문번호 필드에 이메일이 들어간 경우 (잘못된 입력)
    if (orderNumber && orderNumber.includes('@')) {
        // 주문번호 필드의 값을 이메일로 인식하고, guestInfo로 이동
        actualGuestInfo = orderNumber;
        actualOrderNumber = '';
        console.warn('[주문 조회] 주문번호 필드에 이메일이 입력됨, 자동 보정:', orderNumber);
    }
    
    // 이메일 형식인지 전화번호 형식인지 판단
    const isEmail = actualGuestInfo.includes('@');
    // 이메일은 소문자로 변환하고 공백 제거, 전화번호는 숫자만 추출
    const guestEmail = isEmail ? actualGuestInfo.trim().toLowerCase() : null;
    const guestPhone = !isEmail ? actualGuestInfo.replace(/[^0-9]/g, '') : null;
    
    resultDiv.innerHTML = '<p style="text-align:center;color:var(--light);">조회 중...</p>';
    resultDiv.className = 'order-result show';
    
    try {
        const baseUrl = window.DS_BASE_URL || '';
        let url = `${baseUrl}/api/orders.php`;
        const params = [];
        // 실제 주문번호만 전송 (이메일이 아닌 경우만)
        if (actualOrderNumber && !actualOrderNumber.includes('@')) {
            params.push(`orderNumber=${encodeURIComponent(actualOrderNumber)}`);
        }
        if (guestEmail) {
            params.push(`guestEmail=${encodeURIComponent(guestEmail)}`);
        }
        if (guestPhone) {
            params.push(`guestPhone=${encodeURIComponent(guestPhone)}`);
        }
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        console.log('[주문 조회] API 호출:', url);
        console.log('[주문 조회] 요청 파라미터:', { 
            originalOrderNumber: orderNumber,
            actualOrderNumber: actualOrderNumber,
            originalGuestInfo: guestInfo,
            actualGuestInfo: actualGuestInfo,
            guestEmail: guestEmail,
            guestPhone: guestPhone
        });
        
        const response = await fetch(url, {
            credentials: 'include'
        });
        
        console.log('[주문 조회] 응답 상태:', response.status, response.statusText);
        
        // 응답 본문 가져오기
        const responseText = await response.text();
        console.log('[주문 조회] 응답 본문:', responseText);
        
        let orders;
        try {
            orders = JSON.parse(responseText);
            console.log('[주문 조회] 파싱된 주문 데이터:', orders);
        } catch (parseError) {
            console.error('[주문 조회] JSON 파싱 오류:', parseError);
            console.error('[주문 조회] 응답 텍스트:', responseText);
            clearGuestUser();
            resultDiv.className = 'order-result show error';
            resultDiv.innerHTML = `
                <div class="order-result-title">오류가 발생했습니다</div>
                <p style="color:var(--mid);font-size:.9rem;line-height:1.6;">서버 응답을 처리할 수 없습니다. 잠시 후 다시 시도해주세요.</p>
                <p style="color:var(--light);font-size:.8rem;margin-top:.5rem;">오류 상세: ${parseError.message}</p>
            `;
            return;
        }
        
        // 응답 상태 확인 (HTTP 에러인 경우)
        if (!response.ok) {
            clearGuestUser();
            resultDiv.className = 'order-result show error';
            const errorMessage = (orders && orders.message) ? orders.message : `주문 조회 중 오류가 발생했습니다 (HTTP ${response.status})`;
            resultDiv.innerHTML = `
                <div class="order-result-title">${errorMessage}</div>
                <p style="color:var(--mid);font-size:.9rem;line-height:1.6;">입력하신 정보로 주문을 찾을 수 없습니다. 이메일(또는 전화번호)을 다시 확인해주세요.</p>
            `;
            return;
        }
        
        // orders가 배열이 아닌 경우 (에러 응답)
        if (!Array.isArray(orders)) {
            clearGuestUser();
            resultDiv.className = 'order-result show error';
            const errorMessage = (orders && orders.message) ? orders.message : '주문을 찾을 수 없습니다';
            resultDiv.innerHTML = `
                <div class="order-result-title">${errorMessage}</div>
                <p style="color:var(--mid);font-size:.9rem;line-height:1.6;">입력하신 정보로 주문을 찾을 수 없습니다. 이메일(또는 전화번호)을 다시 확인해주세요.</p>
            `;
            return;
        }
        
        if (!orders || orders.length === 0) {
            clearGuestUser();
            resultDiv.className = 'order-result show error';
            resultDiv.innerHTML = `
                <div class="order-result-title">주문을 찾을 수 없습니다</div>
                <p style="color:var(--mid);font-size:.9rem;line-height:1.6;">입력하신 정보로 주문을 찾을 수 없습니다. 이메일(또는 전화번호)을 다시 확인해주세요.</p>
            `;
            return;
        }
        
        console.log('[주문 조회] 조회된 주문 수:', orders.length);
        console.log('[주문 조회] 첫 번째 주문 데이터:', orders[0]);
        console.log('[주문 조회] 첫 번째 주문의 items:', orders[0]?.items);
        
        // 주문 목록 렌더링 (간단한 카드 형태)
        const renderOrderList = (order, index) => {
            const statusClass = order.status === '결제대기' ? 'pending' :
                               order.status === '결제완료' ? 'paid' :
                               order.status === '배송준비중' ? 'preparing' :
                               order.status === '배송중' ? 'shipping' :
                               order.status === '배송완료' ? 'delivered' :
                               order.status === '취소' || order.status === '취소요청' ? 'cancelled' : 'pending';
            
            const orderDate = order.orderedAt || order.createdAt || '';
            const orderId = order.id || order.order_number || `주문-${index + 1}`;
            
            return `
                <div class="order-list-item">
                    <div class="order-list-info">
                        <div class="order-list-header">
                            <span class="order-list-number">${orderId}</span>
                            <span class="status-badge ${statusClass}">${order.status || '결제대기'}</span>
                        </div>
                        <div class="order-list-footer">
                            <span class="order-list-date">${orderDate}</span>
                            <span class="order-list-total">₩${(order.total || 0).toLocaleString()}</span>
                        </div>
                    </div>
                    <button class="order-detail-btn" onclick="showOrderDetail(${index})">상세보기</button>
                </div>
            `;
        };
        
        // 주문 데이터를 전역 변수에 저장 (팝업에서 사용)
        window.orderLookupData = orders;
        
        // 비회원 주문 조회 성공 시 사용자 정보가 localStorage에 저장되지 않도록 보장
        clearGuestUser();
        
        resultDiv.className = 'order-result show';
        resultDiv.innerHTML = `
            <div class="order-result-title" style="margin-bottom:1.5rem;">조회된 주문 (${orders.length}건)</div>
            ${orders.map((order, index) => renderOrderList(order, index)).join('')}
        `;
    } catch (error) {
        console.error('주문 조회 오류:', error);
        clearGuestUser();
        resultDiv.className = 'order-result show error';
        resultDiv.innerHTML = `
            <div class="order-result-title">오류가 발생했습니다</div>
            <p style="color:var(--mid);font-size:.9rem;line-height:1.6;">주문 조회 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.</p>
        `;
    }
});

// 페이지를 떠날 때 비회원 상태 보장 (다른 페이지로 이동 시)
window.addEventListener('beforeunload', function() {
    // 주문 조회 페이지를 떠날 때 무조건 localStorage 정리 (비회원 조회 페이지이므로)
    localStorage.removeItem("ds_current_user");
});

// 페이지 숨김 시에도 정리 (탭 전환 등)
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // 주문 조회 페이지를 떠날 때 무조건 localStorage 정리
        localStorage.removeItem("ds_current_user");
    }
});

// 모든 링크 클릭 시에도 정리
document.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    if (link && link.href && !link.href.includes('order-lookup.php')) {
        // 주문 조회 페이지가 아닌 다른 페이지로 이동하는 경우
        setTimeout(() => {
            localStorage.removeItem("ds_current_user");
        }, 100);
    }
});

// 주문 상세 모달 표시
function showOrderDetail(index) {
    if (!window.orderLookupData || !window.orderLookupData[index]) {
        console.error('[주문 조회] 주문 데이터를 찾을 수 없습니다:', index);
        return;
    }
    
    const order = window.orderLookupData[index];
    const modal = document.getElementById('orderDetailModal');
    const content = document.getElementById('orderDetailContent');
    
    // 주문 상세 정보 렌더링
    const renderOrderDetail = (order) => {
        const statusClass = order.status === '결제대기' ? 'pending' :
                           order.status === '결제완료' ? 'paid' :
                           order.status === '배송준비중' ? 'preparing' :
                           order.status === '배송중' ? 'shipping' :
                           order.status === '배송완료' ? 'delivered' :
                           order.status === '취소' || order.status === '취소요청' ? 'cancelled' : 'pending';
        
        let itemsHtml = '';
        if (order.items && Array.isArray(order.items) && order.items.length > 0) {
            itemsHtml = order.items.map((item) => {
                const itemPrice = (item.price || 0);
                const itemQuantity = (item.quantity || item.qty || 1);
                const totalPrice = itemPrice * itemQuantity;
                return `
                    <div class="order-item">
                        ${item.imageUrl ? `<img src="${item.imageUrl}" class="order-item-image" onerror="this.style.display='none';">` : '<div class="order-item-image" style="background:var(--sage-lighter);"></div>'}
                        <div class="order-item-info">
                            <div class="order-item-name">${item.name || '상품명 없음'}</div>
                            <div class="order-item-details">${item.variant_volume ? item.variant_volume + ' / ' : ''}수량: ${itemQuantity}개</div>
                            <div class="order-item-price">₩${totalPrice.toLocaleString()}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        const canCancel = order.status === '결제대기' || order.status === 'pending' || 
                         order.status === '결제완료' || order.status === 'paid' || 
                         order.status === '배송준비중' || order.status === 'preparing';
        
        return `
            <div class="order-detail-section">
                <div class="order-info-item">
                    <span class="order-info-label">주문번호</span>
                    <span class="order-info-value">${order.id || order.order_number || '정보 없음'}</span>
                </div>
                
                <div class="order-info-item">
                    <span class="order-info-label">주문 상태</span>
                    <span class="order-info-value">
                        <span class="status-badge ${statusClass}">${order.status || '결제대기'}</span>
                    </span>
                </div>
                
                <div class="order-info-item">
                    <span class="order-info-label">주문일</span>
                    <span class="order-info-value">${order.orderedAt || order.createdAt || ''}</span>
                </div>
                
                <div class="order-info-item">
                    <span class="order-info-label">고객명</span>
                    <span class="order-info-value">${order.customer_name || order.customer || '정보 없음'}</span>
                </div>
                
                <div class="order-info-item">
                    <span class="order-info-label">연락처</span>
                    <span class="order-info-value">${order.customer_phone || '정보 없음'}</span>
                </div>
                
                <div class="order-info-item">
                    <span class="order-info-label">배송지</span>
                    <span class="order-info-value" style="text-align:right;line-height:1.6;">${order.customer_address || '정보 없음'}</span>
                </div>
                
                <div class="order-items">
                    <h3 style="font-family:'Cormorant Garamond',serif;font-size:1.3rem;font-weight:600;color:var(--sage);margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:2px solid var(--sage-lighter);">주문 상품</h3>
                    ${itemsHtml || '<p style="color:var(--light);font-size:.9rem;text-align:center;padding:1.5rem;">주문 상품 정보가 없습니다.</p>'}
                </div>
                
                <div style="margin-top:2rem;padding:1.5rem;background:var(--sage-bg);border-radius:12px;border-top:3px solid var(--sage);">
                    <div class="order-info-item" style="border:none;padding:0;">
                        <span class="order-info-label" style="font-size:1.2rem;font-weight:600;color:var(--sage);">총 결제금액</span>
                        <span class="order-info-value" style="font-size:1.5rem;font-weight:700;color:var(--sage);">₩${(order.total || 0).toLocaleString()}</span>
                    </div>
                </div>
                
                ${canCancel ? `
                <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:2px solid var(--border);">
                    <button class="form-btn secondary" onclick="cancelGuestOrder('${order.id || order.order_number}', '${order.email || ''}', '${order.customer_phone || ''}')" style="width:100%;">
                        ${order.status === '결제대기' || order.status === 'pending' ? '주문 취소' : '주문 취소 요청'}
                    </button>
                </div>
                ` : ''}
            </div>
        `;
    };
    
    content.innerHTML = renderOrderDetail(order);
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // 모달 외부 클릭 시 닫기
    modal.addEventListener('click', function(e) {
        if (e.target === modal || e.target.classList.contains('modal-overlay')) {
            closeOrderDetailModal();
        }
    });
}

// 주문 상세 모달 닫기
function closeOrderDetailModal() {
    const modal = document.getElementById('orderDetailModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// 메인 페이지로 이동
function goToMainPage() {
    closeOrderDetailModal();
    window.location.href = '../index.php';
}

// 비회원 주문 취소
async function cancelGuestOrder(orderId, guestEmail, guestPhone) {
    const isPending = window.orderLookupData && window.orderLookupData.find(o => (o.id || o.order_number) === orderId)?.status === '결제대기' || 
                     window.orderLookupData && window.orderLookupData.find(o => (o.id || o.order_number) === orderId)?.status === 'pending';
    
    const confirmMsg = isPending
        ? "정말 주문을 취소하시겠습니까?\n취소 후 복구할 수 없습니다."
        : "정말 주문 취소를 요청하시겠습니까?\n관리자 승인 후 취소됩니다.";
    
    if (!confirm(confirmMsg)) return;
    
    const reason = prompt("취소 사유를 입력해주세요 (선택사항):");
    
    try {
        const baseUrl = window.DS_BASE_URL || '';
        const response = await fetch(`${baseUrl}/api/orders.php`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                orderNumber: orderId,
                action: 'cancel_request',
                reason: reason || '',
                guestEmail: guestEmail || '',
                guestPhone: guestPhone || ''
            })
        });
        
        const result = await response.json();
        
        if (result.ok) {
            alert(result.message || (isPending ? "주문이 취소되었습니다." : "취소 요청이 접수되었습니다. 관리자 승인 후 처리됩니다."));
            closeOrderDetailModal();
            // 주문 목록 새로고침
            document.getElementById('orderLookupForm').dispatchEvent(new Event('submit'));
        } else {
            alert("취소 요청 실패: " + (result.message || "알 수 없는 오류"));
        }
    } catch (error) {
        console.error("주문 취소 요청 오류:", error);
        alert("취소 요청 중 오류가 발생했습니다: " + (error.message || "알 수 없는 오류"));
    }
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeOrderDetailModal();
    }
});

</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
