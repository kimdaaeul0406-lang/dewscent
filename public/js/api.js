(function (w) {
  const USE_MOCK_API = false; // false로 두면 실제 API 엔드포인트로 연결
  const BASE_URL = (() => {
    const root = (w.DS_BASE_URL || "").replace(/\/$/, "");
    return root ? `${root}/api` : "/api";
  })();
  const PRODUCTS_KEY = "dewscent_admin_products"; // LocalStorage 키

  function delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  // 기본 상품 데이터 (초기 seed)
  const defaultProducts = [
    {
      id: 1,
      name: "새벽이슬",
      price: 42000,
      desc: "맑고 투명한 아침이슬의 향기",
      category: "향수",
      badge: "BEST",
      rating: 4.8,
      reviews: 128,
      stock: 50,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-01-01",
    },
    {
      id: 2,
      name: "포근한 오후",
      price: 38000,
      desc: "따뜻하고 포근한 우디 머스크",
      category: "향수",
      badge: "NEW",
      rating: 4.6,
      reviews: 89,
      stock: 35,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-01-05",
    },
    {
      id: 3,
      name: "벚꽃 산책",
      price: 45000,
      desc: "봄날의 벚꽃길을 걷는 듯한 향",
      category: "향수",
      badge: "",
      rating: 4.7,
      reviews: 156,
      stock: 20,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-01-10",
    },
    {
      id: 4,
      name: "비 온 뒤 숲",
      price: 48000,
      desc: "비 온 뒤 숲속의 청량한 공기",
      category: "향수",
      badge: "",
      rating: 4.5,
      reviews: 72,
      stock: 45,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-01-15",
    },
    {
      id: 5,
      name: "달빛 정원",
      price: 52000,
      desc: "밤에 피어나는 은은한 꽃향기",
      category: "향수",
      badge: "BEST",
      rating: 4.9,
      reviews: 203,
      stock: 15,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-01-20",
    },
    {
      id: 6,
      name: "코튼 브리즈",
      price: 35000,
      desc: "갓 빨래한 이불 같은 포근함",
      category: "바디미스트",
      badge: "",
      rating: 4.4,
      reviews: 95,
      stock: 60,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-02-01",
    },
    {
      id: 7,
      name: "레몬 버베나",
      price: 28000,
      desc: "상큼한 레몬과 허브의 조화",
      category: "바디미스트",
      badge: "NEW",
      rating: 4.3,
      reviews: 67,
      stock: 40,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-02-10",
    },
    {
      id: 8,
      name: "피오니 가든",
      price: 32000,
      desc: "화사한 작약 꽃다발의 향기",
      category: "디퓨저",
      badge: "",
      rating: 4.6,
      reviews: 112,
      stock: 25,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-02-15",
    },
  ];

  // LocalStorage에서 상품 가져오기 (없으면 기본값 저장)
  function getStoredProducts() {
    try {
      const stored = localStorage.getItem(PRODUCTS_KEY);
      if (stored) return JSON.parse(stored);
      // 최초 실행 시 기본 상품 저장
      localStorage.setItem(PRODUCTS_KEY, JSON.stringify(defaultProducts));
      return defaultProducts;
    } catch (e) {
      return defaultProducts;
    }
  }

  // LocalStorage에 상품 저장
  function setStoredProducts(products) {
    localStorage.setItem(PRODUCTS_KEY, JSON.stringify(products));
  }

  // 샘플 목업 데이터
  const mock = {
    profile: {
      id: 1,
      name: "홍길동",
      email: "hong@example.com",
      joinedAt: "2025-01-01",
      addresses: [
        {
          id: 1,
          label: "기본",
          recipient: "홍길동",
          address: "서울 강남구 테헤란로 123",
          phone: "010-1234-5678",
        },
      ],
    },
    orders: [
      {
        id: "ORD-2025001",
        total: 32000,
        status: "결제완료",
        orderedAt: "2025-01-15",
      },
      {
        id: "ORD-2025002",
        total: 87000,
        status: "배송중",
        orderedAt: "2025-02-02",
      },
    ],
    users: [
      {
        id: 1,
        name: "홍길동",
        email: "hong@example.com",
        joinedAt: "2025-01-01",
        status: "active",
      },
      {
        id: 2,
        name: "김영희",
        email: "kim@example.com",
        joinedAt: "2025-02-10",
        status: "active",
      },
    ],
    adminOrders: [
      {
        id: "ORD-2025001",
        customer: "홍길동",
        total: 32000,
        status: "결제완료",
        orderedAt: "2025-01-15",
      },
      {
        id: "ORD-2025002",
        customer: "김영희",
        total: 87000,
        status: "배송중",
        orderedAt: "2025-02-02",
      },
    ],
  };

  async function getJSON(path, opts = {}) {
    // BASE_URL이 이미 /api를 포함하므로, path에서 /api 제거
    // 하지만 path가 /api/로 시작하면 제거, 아니면 그대로 사용
    if (path.startsWith("/api/")) {
      path = path.substring(5); // '/api/' 제거 (인덱스 5부터)
    } else if (path.startsWith("/api")) {
      path = path.substring(4); // '/api' 제거 (인덱스 4부터)
    }
    // path가 /로 시작하지 않으면 / 추가
    if (!path.startsWith("/")) {
      path = "/" + path;
    }
    // BASE_URL과 path를 합쳐서 최종 URL 생성
    const finalUrl = `${BASE_URL}${path}`;
    console.log("[API] Request:", finalUrl, opts); // 디버깅용
    const res = await fetch(finalUrl, {
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      ...opts,
    });
    if (!res.ok) {
      let errorMessage = `API Error: ${res.status}`;
      try {
        const errorData = await res.json();
        if (errorData.message) {
          errorMessage = errorData.message;
        }
      } catch (e) {
        // JSON 파싱 실패 시 기본 메시지 사용
      }
      const error = new Error(errorMessage);
      error.status = res.status;
      throw error;
    }
    return await res.json();
  }

  async function postJSON(path, data, opts = {}) {
    // BASE_URL이 이미 /api를 포함하므로, path에서 /api 제거
    if (path.startsWith("/api/")) {
      path = path.substring(5);
    } else if (path.startsWith("/api")) {
      path = path.substring(4);
    }
    // path가 /로 시작하지 않으면 / 추가
    if (!path.startsWith("/")) {
      path = "/" + path;
    }
    // BASE_URL과 path를 합쳐서 최종 URL 생성
    const finalUrl = `${BASE_URL}${path}`;
    console.log("[API] POST Request:", finalUrl, data); // 디버깅용
    const res = await fetch(finalUrl, {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
      ...opts,
    });
    if (!res.ok) {
      let errorMessage = `API Error: ${res.status}`;
      try {
        const errorData = await res.json();
        if (errorData.message) {
          errorMessage = errorData.message;
        }
      } catch (e) {
        // JSON 파싱 실패 시 기본 메시지 사용
      }
      const error = new Error(errorMessage);
      error.status = res.status;
      throw error;
    }
    return await res.json();
  }

  async function putJSON(path, data, opts = {}) {
    // BASE_URL이 이미 /api를 포함하므로, path에서 /api 제거
    if (path.startsWith("/api/")) {
      path = path.substring(5);
    } else if (path.startsWith("/api")) {
      path = path.substring(4);
    }
    // path가 /로 시작하지 않으면 / 추가
    if (!path.startsWith("/")) {
      path = "/" + path;
    }
    // BASE_URL과 path를 합쳐서 최종 URL 생성
    const finalUrl = `${BASE_URL}${path}`;
    console.log("[API] PUT Request:", finalUrl, data); // 디버깅용
    const res = await fetch(finalUrl, {
      method: "PUT",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
      ...opts,
    });
    if (!res.ok) {
      let errorMessage = `API Error: ${res.status}`;
      try {
        const errorData = await res.json();
        if (errorData.message) {
          errorMessage = errorData.message;
        }
      } catch (e) {
        // JSON 파싱 실패 시 기본 메시지 사용
      }
      const error = new Error(errorMessage);
      error.status = res.status;
      throw error;
    }
    return await res.json();
  }

  async function patchJSON(path, data, opts = {}) {
    // BASE_URL이 이미 /api를 포함하므로, path에서 /api 제거
    if (path.startsWith("/api/")) {
      path = path.substring(5);
    } else if (path.startsWith("/api")) {
      path = path.substring(4);
    }
    // path가 /로 시작하지 않으면 / 추가
    if (!path.startsWith("/")) {
      path = "/" + path;
    }
    // BASE_URL과 path를 합쳐서 최종 URL 생성
    const finalUrl = `${BASE_URL}${path}`;
    console.log("[API] PATCH Request:", finalUrl, data); // 디버깅용
    const res = await fetch(finalUrl, {
      method: "PATCH",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
      ...opts,
    });
    
    // 응답 본문 확인
    const text = await res.text();
    let jsonData;
    
    try {
      jsonData = text ? JSON.parse(text) : {};
    } catch (e) {
      console.error("[API] JSON 파싱 실패:", text, e);
      const error = new Error("서버 응답 오류가 발생했습니다. 잠시 후 다시 시도해주세요.");
      error.status = res.status;
      throw error;
    }
    
    if (!res.ok) {
      let errorMessage = jsonData.message || `API Error: ${res.status}`;
      const error = new Error(errorMessage);
      error.status = res.status;
      throw error;
    }
    
    return jsonData;
  }

  // 공개 API (목업/실API 스위치)
  async function getProfile() {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.profile;
    }
    return await getJSON("/me");
  }

  async function getOrders(opts) {
    if (USE_MOCK_API) {
      await delay(200);
      const from = opts?.from ? new Date(opts.from) : null;
      const to = opts?.to ? new Date(opts.to) : null;
      if (!from && !to) return mock.orders;
      return (mock.orders || []).filter((o) => {
        const d = new Date(o.orderedAt);
        if (from && d < from) return false;
        if (to) {
          const toEnd = new Date(to);
          toEnd.setHours(23, 59, 59, 999);
          if (d > toEnd) return false;
        }
        return true;
      });
    }
    const params = new URLSearchParams();
    if (opts?.from) params.set("from", opts.from);
    if (opts?.to) params.set("to", opts.to);
    const qs = params.toString();
    return await getJSON(`/orders.php${qs ? "?" + qs : ""}`);
  }

  async function getUsers() {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.users;
    }
    return await getJSON("/admin/users.php");
  }

  // ========== 문의 API ==========
  async function getInquiries() {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.inquiries || [];
    }
    return await getJSON("/inquiries.php");
  }

  async function createInquiry(data) {
    if (USE_MOCK_API) {
      await delay(200);
      const newInquiry = {
        id: Date.now(),
        ...data,
        status: "waiting",
        createdAt: new Date().toISOString().split("T")[0],
      };
      return { ok: true, inquiry: newInquiry };
    }
    return await getJSON("/inquiries.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  async function updateInquiryAnswer(id, answer) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true };
    }
    return await getJSON("/inquiries.php", {
      method: "PUT",
      body: JSON.stringify({ id, answer }),
    });
  }

  // ========== 리뷰 API ==========
  async function getReviews(productId) {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.reviews?.[productId] || [];
    }
    // productId가 null이면 모든 리뷰 조회 (관리자용)
    const url = productId
      ? `/reviews.php?product_id=${productId}`
      : `/reviews.php`;
    return await getJSON(url);
  }

  async function createReview(productId, data) {
    if (USE_MOCK_API) {
      await delay(200);
      const newReview = {
        id: Date.now(),
        productId,
        ...data,
        date: new Date().toISOString().split("T")[0],
      };
      return { ok: true, review: newReview };
    }
    return await getJSON(`/reviews.php?product_id=${productId}`, {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  async function deleteReview(productId, reviewId = null) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true };
    }
    // reviewId가 있으면 특정 리뷰 삭제, 없으면 해당 상품의 모든 리뷰 삭제
    const url = reviewId
      ? `/reviews.php?product_id=${productId}&review_id=${reviewId}`
      : `/reviews.php?product_id=${productId}`;
    return await getJSON(url, {
      method: "DELETE",
    });
  }

  async function getAdminOrders() {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.adminOrders;
    }
    return await getJSON("/orders.php");
  }

  async function createOrder(orderData) {
    if (USE_MOCK_API) {
      await delay(300);
      return {
        ok: true,
        orderId: Date.now(),
        orderNumber: orderData.id || orderData.orderNumber,
      };
    }
    return await postJSON("/orders.php", orderData);
  }

  async function updateOrderStatus(orderNumber, status) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true, message: '주문 상태가 변경되었습니다.' };
    }
    return await putJSON("/orders.php", { orderNumber, status });
  }

  async function requestOrderCancel(orderNumber, reason = '') {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true, message: '취소 요청이 접수되었습니다.' };
    }
    return await patchJSON("/orders.php", { orderNumber, action: 'cancel_request', reason });
  }

  async function confirmPayment(orderNumber) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true, message: '결제가 확인되었습니다.' };
    }
    return await patchJSON("/orders.php", { orderNumber, action: 'confirm_payment' });
  }

  async function approveCancel(orderNumber) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true, message: '주문 취소가 승인되었습니다.' };
    }
    return await patchJSON("/orders.php", { orderNumber, action: 'approve_cancel' });
  }

  async function rejectCancel(orderNumber) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true, message: '취소 요청이 거부되었습니다.' };
    }
    return await patchJSON("/orders.php", { orderNumber, action: 'reject_cancel' });
  }

  // ========== 상품 CRUD ==========

  // 상품 목록 조회
  async function getProducts() {
    if (USE_MOCK_API) {
      await delay(100);
      return getStoredProducts();
    }
    return await getJSON("/products.php");
  }

  // 상품 단일 조회
  async function getProduct(id) {
    if (USE_MOCK_API) {
      await delay(50);
      const products = getStoredProducts();
      return products.find((p) => p.id === id) || null;
    }
    return await getJSON(`/products.php/${id}`);
  }

  // 상품 등록
  async function createProduct(data) {
    if (USE_MOCK_API) {
      await delay(100);
      const products = getStoredProducts();
      const newId =
        products.length > 0 ? Math.max(...products.map((p) => p.id)) + 1 : 1;
      const newProduct = {
        id: newId,
        name: data.name || "",
        price: parseInt(data.price) || 0,
        desc: data.desc || "",
        category: data.category || "향수",
        badge: data.badge || "",
        rating: parseFloat(data.rating) || 0,
        reviews: parseInt(data.reviews) || 0,
        stock: parseInt(data.stock) || 0,
        status: data.status || "판매중",
        imageUrl: data.imageUrl || "",
        createdAt: new Date().toISOString().split("T")[0],
      };
      products.unshift(newProduct); // 맨 앞에 추가
      setStoredProducts(products);
      return newProduct;
    }
    return await getJSON("/products.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  // 상품 수정
  async function updateProduct(id, data) {
    if (USE_MOCK_API) {
      await delay(100);
      const products = getStoredProducts();
      const idx = products.findIndex((p) => p.id === id);
      if (idx === -1) throw new Error("상품을 찾을 수 없습니다.");
      products[idx] = { ...products[idx], ...data, id }; // id는 변경 불가
      setStoredProducts(products);
      return products[idx];
    }
    return await getJSON(`/products.php/${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    });
  }

  // 상품 삭제
  async function deleteProduct(id) {
    if (USE_MOCK_API) {
      await delay(100);
      const products = getStoredProducts();
      const filtered = products.filter((p) => p.id !== id);
      setStoredProducts(filtered);
      return { success: true };
    }
    return await getJSON(`/products.php/${id}`, {
      method: "DELETE",
    });
  }

  // 프론트엔드용 상품 목록 (판매중인 것만)
  async function getPublicProducts() {
    if (USE_MOCK_API) {
      await delay(50);
      const products = getStoredProducts();
      return products.filter((p) => p.status === "판매중");
    }
    return await getJSON("/products.php");
  }

  // ========== 배너/캐러셀 관리 ==========
  const BANNERS_KEY = "dewscent_banners";
  const defaultBanners = [
    {
      id: 1,
      title: "새로운 향기의 시작",
      subtitle: "DewScent 2025 컬렉션",
      link: "pages/products.php",
      imageUrl: "",
      order: 1,
      active: true,
    },
    {
      id: 2,
      title: "봄의 향기를 담다",
      subtitle: "벚꽃 에디션 출시",
      link: "pages/products.php",
      imageUrl: "",
      order: 2,
      active: true,
    },
    {
      id: 3,
      title: "특별한 선물",
      subtitle: "기프트 세트 20% 할인",
      link: "pages/products.php",
      imageUrl: "",
      order: 3,
      active: true,
    },
    {
      id: 4,
      title: "시그니처 향기",
      subtitle: "베스트셀러 모음",
      link: "pages/products.php",
      imageUrl: "",
      order: 4,
      active: true,
    },
    {
      id: 5,
      title: "신상품 출시",
      subtitle: "한정판 특가",
      link: "pages/products.php",
      imageUrl: "",
      order: 5,
      active: true,
    },
  ];

  function getBanners() {
    try {
      const stored = localStorage.getItem(BANNERS_KEY);
      if (stored) return JSON.parse(stored);
      localStorage.setItem(BANNERS_KEY, JSON.stringify(defaultBanners));
      return defaultBanners;
    } catch {
      return defaultBanners;
    }
  }
  function setBanners(banners) {
    localStorage.setItem(BANNERS_KEY, JSON.stringify(banners));
  }
  function getActiveBanners() {
    return getBanners()
      .filter((b) => b.active)
      .sort((a, b) => a.order - b.order);
  }

  // ========== 팝업 관리 ==========
  const POPUPS_KEY = "dewscent_popups";
  const defaultPopups = [
    {
      id: 1,
      title: "신규 회원 10% 할인",
      content: "첫 구매 시 10% 할인 쿠폰을 드려요!",
      link: "",
      imageUrl: "",
      order: 1,
      active: true,
      startDate: "",
      endDate: "",
    },
  ];

  function getPopups() {
    try {
      const stored = localStorage.getItem(POPUPS_KEY);
      if (stored) return JSON.parse(stored);
      localStorage.setItem(POPUPS_KEY, JSON.stringify(defaultPopups));
      return defaultPopups;
    } catch {
      return defaultPopups;
    }
  }
  function setPopups(popups) {
    localStorage.setItem(POPUPS_KEY, JSON.stringify(popups));
  }
  function getActivePopups() {
    const now = new Date().toISOString().split("T")[0];
    return getPopups()
      .filter((p) => {
        if (!p.active) return false;
        if (p.startDate && p.startDate > now) return false;
        if (p.endDate && p.endDate < now) return false;
        return true;
      })
      .sort((a, b) => a.order - b.order);
  }

  // ========== 메인 상품 배치 관리 ==========
  const MAIN_PRODUCTS_KEY = "dewscent_main_products";
  function getMainProductIds() {
    try {
      const stored = localStorage.getItem(MAIN_PRODUCTS_KEY);
      if (stored) return JSON.parse(stored);
      return []; // 비어있으면 전체 상품 중 상위 4개 표시
    } catch {
      return [];
    }
  }
  function setMainProductIds(ids) {
    localStorage.setItem(MAIN_PRODUCTS_KEY, JSON.stringify(ids));
  }

  // ========== 쿠폰 관리 ==========
  let couponsCache = null;
  let couponsCacheTime = 0;
  const CACHE_DURATION = 60000; // 1분 캐시

  async function getCoupons() {
    try {
      const now = Date.now();
      if (couponsCache && (now - couponsCacheTime) < CACHE_DURATION) {
        return couponsCache;
      }
      
      const response = await fetch(`${BASE_URL}/coupons.php`);
      const data = await response.json();
      
      if (data.success && data.coupons) {
        // DB 필드명을 JavaScript 필드명으로 변환
        couponsCache = data.coupons.map(c => ({
          id: c.id,
          code: c.code,
          name: c.name,
          type: c.type,
          value: c.value,
          minAmount: c.min_amount,
          maxDiscount: c.max_discount,
          startDate: c.start_date || "",
          endDate: c.end_date || "",
          active: c.active == 1,
          usageLimit: c.usage_limit,
          usedCount: c.used_count,
          createdAt: c.created_at ? c.created_at.split(' ')[0] : ""
        }));
        couponsCacheTime = now;
        return couponsCache;
      }
      return [];
    } catch (error) {
      console.error('쿠폰 목록 조회 실패:', error);
      return [];
    }
  }
  
  function setCoupons(coupons) {
    // DB에 저장되므로 더 이상 localStorage 사용 안 함
    // 캐시만 업데이트
    couponsCache = coupons;
    couponsCacheTime = Date.now();
  }
  
  function clearCouponsCache() {
    couponsCache = null;
    couponsCacheTime = 0;
  }
  
  async function getActiveCoupons() {
    const coupons = await getCoupons();
    const now = new Date().toISOString().split("T")[0];
    return coupons.filter((c) => {
      if (!c.active) return false;
      if (c.startDate && c.startDate > now) return false;
      if (c.endDate && c.endDate < now) return false;
      if (c.usageLimit > 0 && c.usedCount >= c.usageLimit) return false;
      return true;
    });
  }
  
  async function validateCoupon(code, amount) {
    try {
      const response = await fetch(`${BASE_URL}/coupons.php?action=validate`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ code, amount })
      });
      
      const data = await response.json();
      
      if (data.success && data.valid) {
        // DB 필드명 변환
        const coupon = data.coupon;
        return {
          valid: true,
          coupon: {
            id: coupon.id,
            code: coupon.code,
            name: coupon.name,
            type: coupon.type,
            value: coupon.value,
            minAmount: coupon.min_amount,
            maxDiscount: coupon.max_discount,
            startDate: coupon.start_date || "",
            endDate: coupon.end_date || "",
            active: coupon.active == 1,
            usageLimit: coupon.usage_limit,
            usedCount: coupon.used_count
          }
        };
      }
      
      return {
        valid: false,
        message: data.message || "유효하지 않은 쿠폰입니다."
      };
    } catch (error) {
      console.error('쿠폰 검증 실패:', error);
      return { valid: false, message: "쿠폰 검증 중 오류가 발생했습니다." };
  }
  }
  
  function applyCoupon(coupon, amount) {
    let discount = 0;
    if (coupon.type === "percent") {
      discount = Math.floor((amount * coupon.value) / 100);
      if (coupon.maxDiscount > 0 && discount > coupon.maxDiscount) {
        discount = coupon.maxDiscount;
      }
    } else {
      discount = coupon.value;
    }
    return Math.min(discount, amount); // 할인금액이 주문금액을 초과하지 않도록
  }

  // ========== 공지사항/이벤트 관리 ==========
  const NOTICES_KEY = "dewscent_notices";
  const defaultNotices = [
    {
      id: 1,
      type: "notice", // notice 또는 event
      title: "신규 회원 10% 할인 이벤트",
      content: "첫 구매 시 10% 할인 쿠폰을 드려요!",
      imageUrl: "",
      link: "",
      startDate: "",
      endDate: "",
      active: true,
      createdAt: new Date().toISOString().split("T")[0],
    },
  ];

  function getNotices() {
    try {
      const stored = localStorage.getItem(NOTICES_KEY);
      if (stored) return JSON.parse(stored);
      localStorage.setItem(NOTICES_KEY, JSON.stringify(defaultNotices));
      return defaultNotices;
    } catch {
      return defaultNotices;
    }
  }
  function setNotices(notices) {
    localStorage.setItem(NOTICES_KEY, JSON.stringify(notices));
  }
  function getActiveNotices() {
    const now = new Date().toISOString().split("T")[0];
    return getNotices()
      .filter((n) => {
        if (!n.active) return false;
        if (n.startDate && n.startDate > now) return false;
        if (n.endDate && n.endDate < now) return false;
        return true;
      })
      .sort((a, b) => {
        // 이벤트가 공지사항보다 먼저, 그 다음 날짜순
        if (a.type !== b.type) return a.type === "event" ? -1 : 1;
        return new Date(b.createdAt) - new Date(a.createdAt);
      });
  }

  // ========== 사이트 설정 ==========
  const SITE_SETTINGS_KEY = "dewscent_site_settings";
  const defaultSiteSettings = {
    siteName: "DewScent",
    siteSlogan: "당신의 향기를 찾아서",
    contactEmail: "hello@dewscent.kr",
    contactPhone: "02-1234-5678",
    address: "서울시 강남구 테헤란로 123",
    businessHours: "평일 10:00 ~ 17:00",
    kakaoChannel: "듀센트 고객센터",
    instagramUrl: "https://instagram.com",
  };

  function getSiteSettings() {
    try {
      const stored = localStorage.getItem(SITE_SETTINGS_KEY);
      if (stored) return { ...defaultSiteSettings, ...JSON.parse(stored) };
      return defaultSiteSettings;
    } catch {
      return defaultSiteSettings;
    }
  }
  function setSiteSettings(settings) {
    localStorage.setItem(SITE_SETTINGS_KEY, JSON.stringify(settings));
  }

  // ========== 감정 섹션 관리 ==========
  const EMOTIONS_KEY = "dewscent_emotions";
  const defaultEmotions = [
    {
      id: 1,
      key: "calm",
      title: "차분해지고 싶은 날",
      desc: "마음이 고요해지는 향",
      order: 1,
      active: true,
    },
    {
      id: 2,
      key: "warm",
      title: "따뜻함이 필요한 순간",
      desc: "포근한 온기를 담은 향",
      order: 2,
      active: true,
    },
    {
      id: 3,
      key: "fresh",
      title: "상쾌하게 시작하고 싶을 때",
      desc: "기분 좋은 청량감",
      order: 3,
      active: true,
    },
    {
      id: 4,
      key: "romantic",
      title: "설레는 마음을 담아",
      desc: "로맨틱한 플로럴 노트",
      order: 4,
      active: true,
    },
  ];

  function getEmotions() {
    try {
      const stored = localStorage.getItem(EMOTIONS_KEY);
      if (stored) return JSON.parse(stored);
      localStorage.setItem(EMOTIONS_KEY, JSON.stringify(defaultEmotions));
      return defaultEmotions;
    } catch {
      return defaultEmotions;
    }
  }
  function setEmotions(emotions) {
    localStorage.setItem(EMOTIONS_KEY, JSON.stringify(emotions));
  }
  function getActiveEmotions() {
    return getEmotions()
      .filter((e) => e.active)
      .sort((a, b) => a.order - b.order);
  }

  // ========== 섹션 타이틀 관리 ==========
  const SECTIONS_KEY = "dewscent_sections";
  const defaultSections = {
    emotionLabel: "FIND YOUR SCENT",
    emotionTitle: "오늘, 어떤 기분인가요?",
    emotionSubtitle: "감정에 맞는 향기를 추천해드릴게요",
    bestLabel: "BEST SELLERS",
    bestTitle: "가장 사랑받는 향기",
    bestSubtitle: "많은 분들이 선택한 듀센트의 베스트셀러",
    bestQuote: "— 향기는 기억을 여는 열쇠 —",
  };

  function getSections() {
    try {
      const stored = localStorage.getItem(SECTIONS_KEY);
      if (stored) return { ...defaultSections, ...JSON.parse(stored) };
      return defaultSections;
    } catch {
      return defaultSections;
    }
  }
  function setSections(sections) {
    localStorage.setItem(SECTIONS_KEY, JSON.stringify(sections));
  }

  // ========== 감정별 추천 상품 관리 ==========
  const EMOTION_RECOMMENDATIONS_KEY = "dewscent_emotion_recommendations";

  // 감정별 추천 상품 가져오기 (7일 주기)
  async function getEmotionRecommendations(emotionKey) {
    if (USE_MOCK_API) {
      await delay(50);

      // 관리자가 설정한 추천 상품이 있으면 사용
      const stored = localStorage.getItem(EMOTION_RECOMMENDATIONS_KEY);
      if (stored) {
        try {
          const recommendations = JSON.parse(stored);
          const emotionRecs = recommendations[emotionKey];
          if (
            emotionRecs &&
            emotionRecs.productIds &&
            emotionRecs.productIds.length > 0
          ) {
            const products = getStoredProducts();
            // 중복 제거: Set을 사용하여 고유한 ID만 유지
            const uniqueIds = [...new Set(emotionRecs.productIds)];
            const recommendedProducts = uniqueIds
              .map((id) => products.find((p) => p.id === id))
              .filter((p) => p && p.status === "판매중")
              .filter(
                (p, index, self) =>
                  // id 기준으로 중복 제거
                  index === self.findIndex((prod) => prod.id === p.id)
              );

            if (recommendedProducts.length > 0) {
              // 7일 주기로 다른 상품 추천
              return getWeeklyRotatedProducts(recommendedProducts, emotionKey);
            }
          }
        } catch (e) {
          console.error("Error parsing recommendations:", e);
        }
      }

      // 관리자 설정이 없으면 자동 추천
      return getAutoEmotionRecommendations(emotionKey);
    }
    return await getJSON(`/emotions/${emotionKey}/recommendations`);
  }

  // 7일 주기로 상품 순환 (중복 제거)
  function getWeeklyRotatedProducts(products, emotionKey) {
    // 중복 제거: id 기준으로 고유한 상품만 유지
    const uniqueProducts = products.filter(
      (p, index, self) => index === self.findIndex((prod) => prod.id === p.id)
    );

    if (uniqueProducts.length <= 4) return uniqueProducts.slice(0, 4);

    const daysSinceEpoch = Math.floor(Date.now() / (1000 * 60 * 60 * 24));
    const weekCycle = Math.floor(daysSinceEpoch / 7);
    const seed = weekCycle + emotionKey.charCodeAt(0);

    // 시드 기반 셔플
    const shuffled = [...uniqueProducts].sort((a, b) => {
      const hashA = (a.id * seed) % 1000;
      const hashB = (b.id * seed) % 1000;
      return hashA - hashB;
    });

    // 중복 없이 4개 반환
    const result = [];
    const seenIds = new Set();
    for (const product of shuffled) {
      if (!seenIds.has(product.id)) {
        result.push(product);
        seenIds.add(product.id);
        if (result.length >= 4) break;
      }
    }

    return result;
  }

  // 자동 추천 (관리자 설정이 없을 때)
  function getAutoEmotionRecommendations(emotionKey) {
    const allProducts = getStoredProducts().filter(
      (p) => p.status === "판매중"
    );

    // 중복 제거: id 기준으로 고유한 상품만 유지
    const uniqueProducts = allProducts.filter(
      (p, index, self) => index === self.findIndex((prod) => prod.id === p.id)
    );

    // 감정별 카테고리 매핑
    const emotionCategoryMap = {
      calm: ["향수", "디퓨저"],
      warm: ["향수", "바디미스트"],
      fresh: ["바디미스트", "섬유유연제"],
      romantic: ["향수", "바디미스트"],
      focus: ["향수", "디퓨저"],
      refresh: ["바디미스트", "섬유유연제"],
    };

    const categories = emotionCategoryMap[emotionKey] || ["향수"];
    let filtered = uniqueProducts.filter((p) =>
      categories.includes(p.category)
    );

    if (filtered.length === 0) {
      filtered = uniqueProducts; // 카테고리 매칭 실패 시 전체 상품
    }

    return getWeeklyRotatedProducts(filtered, emotionKey);
  }

  // 감정별 추천 상품 설정 (관리자용)
  function setEmotionRecommendations(emotionKey, productIds) {
    try {
      const stored = localStorage.getItem(EMOTION_RECOMMENDATIONS_KEY);
      const recommendations = stored ? JSON.parse(stored) : {};
      // 중복 제거: Set을 사용하여 고유한 ID만 저장
      const uniqueIds = [...new Set(productIds)];
      recommendations[emotionKey] = {
        productIds: uniqueIds,
        updatedAt: new Date().toISOString(),
      };
      localStorage.setItem(
        EMOTION_RECOMMENDATIONS_KEY,
        JSON.stringify(recommendations)
      );
    } catch (e) {
      console.error("Error setting recommendations:", e);
    }
  }

  // 모든 감정별 추천 상품 가져오기 (관리자용)
  function getAllEmotionRecommendations() {
    try {
      const stored = localStorage.getItem(EMOTION_RECOMMENDATIONS_KEY);
      return stored ? JSON.parse(stored) : {};
    } catch {
      return {};
    }
  }

  w.API = {
    getProfile,
    getOrders,
    getUsers,
    getAdminOrders,
    createOrder,
    updateOrderStatus,
    requestOrderCancel,
    confirmPayment,
    approveCancel,
    rejectCancel,
    // 문의 관련
    getInquiries,
    createInquiry,
    updateInquiryAnswer,
    // 리뷰 관련
    getReviews,
    createReview,
    deleteReview,
    // 상품 관련
    getProducts,
    getProduct,
    createProduct,
    updateProduct,
    deleteProduct,
    getPublicProducts,
    // 배너/캐러셀
    getBanners,
    setBanners,
    getActiveBanners,
    // 팝업
    getPopups,
    setPopups,
    getActivePopups,
    // 메인 상품 배치
    getMainProductIds,
    setMainProductIds,
    // 사이트 설정
    getSiteSettings,
    setSiteSettings,
    // 감정 섹션
    getEmotions,
    setEmotions,
    getActiveEmotions,
    // 섹션 타이틀
    getSections,
    setSections,
    // 감정별 추천 상품
    getEmotionRecommendations,
    setEmotionRecommendations,
    getAllEmotionRecommendations,
    // 쿠폰
    getCoupons,
    setCoupons,
    clearCouponsCache,
    getActiveCoupons,
    validateCoupon,
    applyCoupon,
    // 공지사항/이벤트
    getNotices,
    setNotices,
    getActiveNotices,
    // 배송 추적 시뮬레이션
    simulateShipping: function (orderId) {
      const ORDER_DETAILS_KEY = "dewscent_order_details";
      let orderDetails = {};
      try {
        const stored = localStorage.getItem(ORDER_DETAILS_KEY);
        if (stored) orderDetails = JSON.parse(stored);
      } catch {}

      const order = orderDetails[orderId];
      if (!order || !order.tracking) return order;

      const now = new Date();
      const today = now.toISOString().split("T")[0];
      const time = `${String(now.getHours()).padStart(2, "0")}:${String(
        now.getMinutes()
      ).padStart(2, "0")}`;

      // 주문일로부터 경과 시간 계산 (시뮬레이션)
      const orderDate = new Date(order.orderedAt);
      const daysDiff = Math.floor((now - orderDate) / (1000 * 60 * 60 * 24));

      // 배송 상태 업데이트 (시뮬레이션)
      if (order.status === "결제대기") {
        // 결제 완료 시뮬레이션 (1일 후)
        if (daysDiff >= 1) {
          order.status = "결제완료";
          order.tracking.history.push({
            status: "결제완료",
            date: today,
            time: time,
            message: "결제가 완료되었습니다.",
          });
        }
      } else if (order.status === "결제완료") {
        // 배송 준비 시뮬레이션 (2일 후)
        if (daysDiff >= 2) {
          order.status = "배송준비중";
          order.tracking.history.push({
            status: "배송준비중",
            date: today,
            time: time,
            message: "상품을 포장하고 있습니다.",
          });
        }
      } else if (order.status === "배송준비중") {
        // 배송 시작 시뮬레이션 (3일 후)
        if (daysDiff >= 3 && !order.tracking.number) {
          order.status = "배송중";
          // 운송장 번호 생성 (시뮬레이션)
          order.tracking.number = `1234567890${String(order.id).slice(-4)}`;
          order.tracking.history.push({
            status: "배송중",
            date: today,
            time: time,
            message: "배송이 시작되었습니다.",
          });
        }
      } else if (order.status === "배송중") {
        // 배송 완료 시뮬레이션 (5일 후)
        if (daysDiff >= 5) {
          order.status = "배송완료";
          order.tracking.history.push({
            status: "배송완료",
            date: today,
            time: time,
            message: "배송이 완료되었습니다.",
          });
        } else if (daysDiff >= 4) {
          // 배송 중간 업데이트
          const lastUpdate =
            order.tracking.history[order.tracking.history.length - 1];
          if (
            lastUpdate &&
            lastUpdate.status === "배송중" &&
            lastUpdate.date !== today
          ) {
            order.tracking.history.push({
              status: "배송중",
              date: today,
              time: time,
              message: "배송 중입니다.",
            });
          }
        }
      }

      // 주문 상세 정보 업데이트
      orderDetails[orderId] = order;
      localStorage.setItem(ORDER_DETAILS_KEY, JSON.stringify(orderDetails));

      // 주문 목록도 업데이트
      const ORDER_ADDS_KEY = "dewscent_order_adds";
      let adds = [];
      try {
        const stored = localStorage.getItem(ORDER_ADDS_KEY);
        if (stored) adds = JSON.parse(stored);
      } catch {}

      const orderInList = adds.find((o) => o.id === orderId);
      if (orderInList) {
        orderInList.status = order.status;
        localStorage.setItem(ORDER_ADDS_KEY, JSON.stringify(adds));
      }

      return order;
    },
    // 유틸
    __MOCK__: mock,
    __getStoredProducts: getStoredProducts,
    __setStoredProducts: setStoredProducts,
  };
})(window);
