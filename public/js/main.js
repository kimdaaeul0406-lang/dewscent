// DewScent 메인 스크립트

// ───────────────────────────
// 1. 상품 / 리뷰 / 테스트 데이터
// ───────────────────────────

// 상품 데이터 - API에서 로드 (관리자가 등록한 상품)
let products = [];

// 상품 데이터 로드 함수
async function loadProducts() {
  try {
    if (typeof API !== "undefined" && API.getPublicProducts) {
      products = await API.getPublicProducts();
    } else {
      // API가 없으면 fallback (하드코딩 데이터)
      products = getDefaultProducts();
    }
  } catch (e) {
    console.error("상품 로드 실패:", e);
    products = getDefaultProducts();
  }
}

// 기본 상품 데이터 (API 실패 시 fallback)
function getDefaultProducts() {
  return [
    {
      id: 1,
      name: "Morning Dew",
      type: "향수",
      category: "향수",
      price: 89000,
      originalPrice: 110000,
      rating: 4.8,
      reviews: 128,
      badge: "BEST",
      desc: "아침 이슬처럼 맑고 청량한 향기입니다.",
    },
    {
      id: 2,
      name: "Rose Garden",
      type: "바디미스트",
      category: "바디미스트",
      price: 65000,
      originalPrice: null,
      rating: 4.9,
      reviews: 89,
      badge: "NEW",
      desc: "로맨틱한 장미 정원을 거니는 듯한 우아한 향기입니다.",
    },
    {
      id: 3,
      name: "Golden Hour",
      type: "향수",
      category: "향수",
      price: 105000,
      originalPrice: null,
      rating: 4.7,
      reviews: 156,
      badge: null,
      desc: "황금빛 노을처럼 따스하고 포근한 향기입니다.",
    },
    {
      id: 4,
      name: "Forest Mist",
      type: "디퓨저",
      category: "디퓨저",
      price: 78000,
      originalPrice: 98000,
      rating: 4.6,
      reviews: 72,
      badge: "SALE",
      desc: "숲속의 신선한 공기를 담은 청량한 향기입니다.",
    },
    {
      id: 5,
      name: "Ocean Breeze",
      type: "섬유유연제",
      category: "섬유유연제",
      price: 32000,
      originalPrice: null,
      rating: 4.5,
      reviews: 203,
      badge: null,
      desc: "바다 바람처럼 시원하고 깨끗한 향기입니다.",
    },
    {
      id: 6,
      name: "Velvet Night",
      type: "향수",
      category: "향수",
      price: 125000,
      originalPrice: null,
      rating: 4.9,
      reviews: 67,
      badge: "NEW",
      desc: "밤의 신비로움을 담은 관능적인 향기입니다.",
    },
    {
      id: 7,
      name: "Citrus Burst",
      type: "바디미스트",
      category: "바디미스트",
      price: 55000,
      originalPrice: 68000,
      rating: 4.4,
      reviews: 145,
      badge: "SALE",
      desc: "상큼한 시트러스가 톡톡 터지는 활기찬 향기입니다.",
    },
    {
      id: 8,
      name: "Soft Cotton",
      type: "섬유유연제",
      category: "섬유유연제",
      price: 28000,
      originalPrice: null,
      rating: 4.7,
      reviews: 312,
      badge: "BEST",
      desc: "갓 세탁한 면처럼 포근하고 깨끗한 향기입니다.",
    },
  ];
}

// 리뷰 시스템 (LocalStorage 기반)
const REVIEWS_KEY = "dewscent_reviews";

// 기본 리뷰 데이터 (초기 seed)
const defaultReviews = {
  1: [
    {
      id: 1,
      user: "김**",
      date: "2024.01.15",
      rating: 5,
      content:
        "정말 좋은 향이에요! 오래 지속되고 은은해서 데일리로 사용하기 좋습니다.",
    },
    {
      id: 2,
      user: "이**",
      date: "2024.01.12",
      rating: 5,
      content:
        "선물용으로 구매했는데 포장도 예쁘고 향도 너무 좋아서 만족합니다.",
    },
  ],
  2: [
    {
      id: 3,
      user: "박**",
      date: "2024.01.10",
      rating: 4,
      content:
        "향이 좋긴 한데 지속력이 조금 아쉬워요. 그래도 재구매 의사 있습니다!",
    },
  ],
  3: [
    {
      id: 4,
      user: "최**",
      date: "2024.01.08",
      rating: 5,
      content: "포근하고 따뜻한 향이에요. 겨울에 딱입니다.",
    },
  ],
};

// 리뷰 데이터 가져오기
function getAllReviews() {
  try {
    const stored = localStorage.getItem(REVIEWS_KEY);
    if (stored) return JSON.parse(stored);
    localStorage.setItem(REVIEWS_KEY, JSON.stringify(defaultReviews));
    return defaultReviews;
  } catch {
    return defaultReviews;
  }
}

// 특정 상품의 리뷰 가져오기
function getProductReviews(productId) {
  const allReviews = getAllReviews();
  return allReviews[productId] || [];
}

// 내 리뷰 삭제
async function deleteMyReview(reviewId, productId) {
  if (!confirm("정말 이 리뷰를 삭제하시겠습니까?")) return;

  try {
    const result = await API.deleteReview(productId);
    if (result.ok) {
      // 리뷰 목록 갱신
      openReviewList();
      renderReviews();

      // 상품 정보 새로고침 (평점 업데이트)
      if (typeof loadProducts === "function") {
        loadProducts();
      }

      alert("리뷰가 삭제되었습니다.");
    } else {
      alert(result.message || "리뷰 삭제 중 오류가 발생했습니다.");
    }
  } catch (e) {
    console.error("리뷰 삭제 오류:", e);
    alert("리뷰 삭제 중 오류가 발생했습니다.");
  }
}

// 리뷰 저장
function saveReview(productId, reviewData) {
  const allReviews = getAllReviews();
  if (!allReviews[productId]) {
    allReviews[productId] = [];
  }
  const newId = Date.now();
  const newReview = {
    id: newId,
    ...reviewData,
    date: new Date()
      .toLocaleDateString("ko-KR")
      .replace(/\. /g, ".")
      .replace(".", ""),
  };
  allReviews[productId].unshift(newReview);
  localStorage.setItem(REVIEWS_KEY, JSON.stringify(allReviews));
  return newReview;
}

let cart = [];

// 토스페이먼츠 결제위젯 인스턴스
let paymentWidgets = null;
let paymentWidgetInitialized = false;
let currentProduct = null;
let currentTestStep = 0;
let testAnswers = [];

const testQuestions = [
  {
    question: "어떤 계절을 가장 좋아하시나요?",
    options: [
      "봄 - 새로운 시작의 설렘",
      "여름 - 활기찬 에너지",
      "가을 - 차분한 여유",
      "겨울 - 포근한 따스함",
    ],
  },
  {
    question: "주로 어떤 상황에서 향기를 사용하시나요?",
    options: [
      "데일리 - 일상적인 외출",
      "오피스 - 직장/학교",
      "데이트 - 특별한 만남",
      "홈 - 집에서 휴식",
    ],
  },
  {
    question: "선호하는 향의 느낌은?",
    options: [
      "상쾌하고 가벼운",
      "달콤하고 부드러운",
      "깊고 신비로운",
      "깨끗하고 청량한",
    ],
  },
  {
    question: "좋아하는 자연 환경은?",
    options: ["꽃이 만개한 정원", "푸른 숲속", "따스한 해변", "평화로운 호수"],
  },
  {
    question: "어떤 분위기를 연출하고 싶으신가요?",
    options: [
      "우아하고 세련된",
      "활발하고 밝은",
      "편안하고 자연스러운",
      "신비롭고 매력적인",
    ],
  },
  {
    question: "향의 지속력은 어느 정도를 원하시나요?",
    options: [
      "가볍게 은은하게",
      "적당히 오래",
      "진하게 오랫동안",
      "상황에 따라 다르게",
    ],
  },
];

const scentResults = {
  floral: {
    name: "플로럴 타입",
    desc: "꽃향기를 기반으로 한 로맨틱하고 우아한 향기가 어울려요.",
    products: [0, 1],
  },
  fresh: {
    name: "프레시 타입",
    desc: "시트러스와 그린 계열의 상쾌하고 활기찬 향기가 어울려요.",
    products: [4, 6],
  },
  woody: {
    name: "우디 타입",
    desc: "나무와 숲의 깊이있는 자연적인 향기가 어울려요.",
    products: [3, 2],
  },
  oriental: {
    name: "오리엔탈 타입",
    desc: "따뜻하고 신비로운 동양적인 향기가 어울려요.",
    products: [5, 2],
  },
};

// ───────────────────────────
// 2. 인트로 / 웰컴 팝업
// ───────────────────────────
const INTRO_SEEN_KEY = "dewscent_intro_seen";

// 인트로 숨기기 함수
function hideIntro() {
  const intro = document.getElementById("intro");
  const main = document.getElementById("main");

  if (!intro) {
    // 인트로가 없으면 메인만 표시
    if (main) {
      main.classList.add("visible");
      document.body.style.overflow = "";
    }
    return;
  }

  if (!main) {
    console.error("main 요소를 찾을 수 없습니다.");
    // 인트로만 숨기기
    intro.classList.add("hidden");
    document.body.style.overflow = "";
    return;
  }

  // 인트로 숨기기
  intro.classList.add("hidden");
  main.classList.add("visible");
  // 인트로 사라지면 스크롤 복원
  document.body.style.overflow = "";

  // 웰컴 팝업 표시 (일주일간 안보기 확인)
  setTimeout(() => {
    if (!isWelcomePopupHidden()) {
      const welcome = document.getElementById("welcomePopup");
      if (welcome) welcome.classList.add("active");
    }
  }, 1000);
}

// 뒤로가기로 돌아온 경우 감지
window.addEventListener("pageshow", function (event) {
  // persisted가 true면 뒤로가기/앞으로가기로 돌아온 경우
  if (event.persisted) {
    // 인트로를 즉시 숨김
    const intro = document.getElementById("intro");
    if (intro) {
      intro.classList.add("hidden");
      const main = document.getElementById("main");
      if (main) main.classList.add("visible");
      document.body.style.overflow = "";
    }
  }
});

// 인트로 표시 (첫 방문 또는 새로고침 시에만)
// performance.navigation.type이 0이면 직접 방문, 1이면 새로고침
const isReload =
  performance.navigation.type === 1 ||
  (performance.getEntriesByType &&
    performance.getEntriesByType("navigation")[0]?.type === "reload");

// 인트로가 표시되는 동안 메인 스크롤 잠금
// DOMContentLoaded 이벤트에서 실행하여 DOM이 완전히 로드된 후 실행
function initIntro() {
  // 주문 완료 후에는 인트로를 표시하지 않음
  const urlParams = new URLSearchParams(window.location.search);
  const orderId = urlParams.get("order");

  if (orderId) {
    // 주문 완료 페이지인 경우 인트로를 즉시 숨김
    const introEl = document.getElementById("intro");
    const mainEl = document.getElementById("main");

    if (introEl) {
      introEl.classList.add("hidden");
    }
    if (mainEl) {
      mainEl.classList.add("visible");
    }
    document.body.style.overflow = "";
    return;
  }

  const introEl = document.getElementById("intro");
  const mainEl = document.getElementById("main");

  if (introEl && !introEl.classList.contains("hidden")) {
    document.body.style.overflow = "hidden";

    // 2.5초 후 인트로 자동으로 숨기기
    setTimeout(() => {
      hideIntro();
    }, 2500);
  } else if (!introEl) {
    // 인트로 요소가 없으면 메인을 바로 표시
    if (mainEl) {
      mainEl.classList.add("visible");
      document.body.style.overflow = "";
    }
  } else {
    // 인트로가 이미 숨겨져 있으면 메인 표시
    if (mainEl) {
      mainEl.classList.add("visible");
      document.body.style.overflow = "";
    }
  }
}

// DOM이 로드되면 인트로 초기화
// 여러 방법으로 실행 보장하여 확실하게 실행되도록 함
(function () {
  function runInitIntro() {
    try {
      if (typeof initIntro === "function") {
        initIntro();
      } else {
        console.error("initIntro 함수가 정의되지 않았습니다.");
        // 함수가 없어도 메인은 표시
        const main = document.getElementById("main");
        const intro = document.getElementById("intro");
        if (main) main.classList.add("visible");
        if (intro) intro.classList.add("hidden");
        document.body.style.overflow = "";
      }
    } catch (error) {
      console.error("인트로 초기화 오류:", error);
      // 에러가 발생해도 메인은 표시
      const main = document.getElementById("main");
      const intro = document.getElementById("intro");
      if (main) main.classList.add("visible");
      if (intro) intro.classList.add("hidden");
      document.body.style.overflow = "";
    }
  }

  // 즉시 실행 시도
  if (document.readyState === "complete") {
    runInitIntro();
  } else if (document.readyState === "interactive") {
    runInitIntro();
  } else {
    document.addEventListener("DOMContentLoaded", runInitIntro);
  }

  // 안전장치: window.onload에서도 실행
  window.addEventListener("load", runInitIntro);

  // 최종 안전장치: 약간의 지연 후에도 실행
  setTimeout(runInitIntro, 100);
})();

// 웰컴 팝업 일주일간 안보기
const WELCOME_HIDE_KEY = "dewscent_welcome_hidden";

function isWelcomePopupHidden() {
  try {
    const hideUntil = localStorage.getItem(WELCOME_HIDE_KEY);
    if (!hideUntil) return false;
    return Date.now() < parseInt(hideUntil);
  } catch {
    return false;
  }
}

function hideWelcomePopupWeek() {
  localStorage.setItem(WELCOME_HIDE_KEY, Date.now() + 7 * 24 * 60 * 60 * 1000);
  closePopup();
}

// ───────────────────────────
// 3. 메인 슬라이더 (관리자 배너 연동)
// ───────────────────────────
let currentSlide = 2;
const positions = [
  "pos-far-left",
  "pos-left",
  "pos-center",
  "pos-right",
  "pos-far-right",
];
let sliderInterval;

// 관리자가 등록한 배너 로드
function loadBannerSlider() {
  const track = document.getElementById("sliderTrack");
  const dotsContainer = document.getElementById("sliderDots");
  if (!track || !dotsContainer) return;

  // 기본 배너 (관리자 배너 없을 때)
  let banners = [
    {
      id: 1,
      title: "Spring Collection",
      subtitle: "봄의 시작을 알리는 향기",
      link: "pages/products.php",
      imageUrl: "",
    },
    {
      id: 2,
      title: "Rose Edition",
      subtitle: "로맨틱한 장미 향기",
      link: "pages/products.php",
      imageUrl: "",
    },
    {
      id: 3,
      title: "Golden Moment",
      subtitle: "황금빛 순간을 담다",
      link: "pages/products.php",
      imageUrl: "",
    },
    {
      id: 4,
      title: "Forest Breeze",
      subtitle: "숲속의 신선한 바람",
      link: "pages/products.php",
      imageUrl: "",
    },
    {
      id: 5,
      title: "Sunset Glow",
      subtitle: "노을빛 따스함",
      link: "pages/products.php",
      imageUrl: "",
    },
  ];

  // 관리자 배너가 있으면 사용
  if (typeof API !== "undefined" && API.getActiveBanners) {
    const adminBanners = API.getActiveBanners();
    if (adminBanners.length > 0) {
      // 관리자가 등록한 배너 사용 (최대 5개)
      banners = adminBanners.slice(0, 5);
      // 5개 미만이면 반복해서 채움
      if (banners.length < 5) {
        const originalBanners = [...banners];
        while (banners.length < 5) {
          originalBanners.forEach((b) => {
            if (banners.length < 5) banners.push(b);
          });
        }
      }
    }
  }

  // 슬라이드 카드 생성
  track.innerHTML = banners
    .map(
      (b, i) => `
    <div class="slide-card ${positions[i]}" onclick="handleBannerClick('${
        b.link || "pages/products.php"
      }')">
      <div class="slide-card-image" ${
        b.imageUrl
          ? `style="background-image:url(${b.imageUrl});background-size:cover;background-position:center;"`
          : ""
      }>
        ${!b.imageUrl ? `이벤트 ${i + 1}` : ""}
      </div>
      <div class="slide-card-info">
        <p class="slide-card-title">${b.title}</p>
        <p class="slide-card-desc">${b.subtitle || ""}</p>
      </div>
    </div>
  `
    )
    .join("");

  // 슬라이더 점 생성
  dotsContainer.innerHTML = banners
    .map(
      (b, i) => `
    <div class="slider-dot ${i === 2 ? "active" : ""}" data-index="${i}"></div>
  `
    )
    .join("");

  // 점 클릭 이벤트 재설정
  document.querySelectorAll(".slider-dot").forEach((dot) => {
    dot.addEventListener("click", () => {
      goToSlide(parseInt(dot.dataset.index, 10));
    });
  });

  currentSlide = 2;
  updateSlider();
}

function updateSlider() {
  const cards = document.querySelectorAll(".slide-card");
  const dots = document.querySelectorAll(".slider-dot");

  if (cards.length === 0) return;

  cards.forEach((card, index) => {
    card.className = "slide-card";
    const posIndex = (index - currentSlide + 2 + cards.length) % cards.length;
    if (positions[posIndex]) card.classList.add(positions[posIndex]);
  });

  dots.forEach((dot, index) => {
    dot.classList.toggle("active", index === currentSlide);
  });
}

function nextSlide() {
  currentSlide = (currentSlide + 1) % 5;
  updateSlider();
}

function goToSlide(index) {
  currentSlide = index;
  updateSlider();
  clearInterval(sliderInterval);
  sliderInterval = setInterval(nextSlide, 4000);
}

// 배너 클릭 시 링크 이동
function handleBannerClick(link) {
  if (link && link !== "#" && link.trim() !== "") {
    // 상대 경로 처리
    if (link.startsWith("http://") || link.startsWith("https://")) {
      window.location.href = link;
    } else {
      // 상대 경로인 경우
      window.location.href = link;
    }
  }
}

// 슬라이더 초기화 (관리자 배너 로드)
loadBannerSlider();

// 자동 슬라이드 시작
sliderInterval = setInterval(nextSlide, 4000);

// 감정 섹션 동적 로드
function loadEmotionSection() {
  const grid = document.getElementById("emotionGrid");
  if (!grid) return;

  // 기본 감정 데이터
  let emotions = [
    {
      id: 1,
      key: "calm",
      title: "차분해지고 싶은 날",
      desc: "마음이 고요해지는 향",
    },
    {
      id: 2,
      key: "warm",
      title: "따뜻함이 필요한 순간",
      desc: "포근한 온기를 담은 향",
    },
    {
      id: 3,
      key: "focus",
      title: "집중하고 싶은 시간",
      desc: "맑고 깨끗한 향",
    },
    {
      id: 4,
      key: "refresh",
      title: "상쾌하고 싶을 때",
      desc: "활력을 주는 향",
    },
  ];

  // 관리자 감정 데이터
  if (typeof API !== "undefined" && API.getActiveEmotions) {
    const adminEmotions = API.getActiveEmotions();
    if (adminEmotions.length > 0) {
      emotions = adminEmotions;
    }
  }

  grid.innerHTML = emotions
    .map(
      (e) => `
    <div class="emotion-card ${e.key}" data-emotion="${e.key}">
      <div class="emotion-visual"></div>
      <h3 class="emotion-title">${e.title}</h3>
      <p class="emotion-desc">${e.desc}</p>
    </div>
  `
    )
    .join("");

  // 감정 카드 클릭 이벤트
  grid.querySelectorAll(".emotion-card").forEach((card) => {
    card.addEventListener("click", () => {
      const emotion = card.dataset.emotion;
      const emotionData = emotions.find((e) => e.key === emotion);
      openEmotionRecommendation(emotion, emotionData);
    });
  });
}

// 감정별 향수 추천 모달 열기
async function openEmotionRecommendation(emotionKey, emotionData) {
  // 7일 주기로 추천 상품 가져오기
  const recommendations = await getEmotionRecommendations(emotionKey);

  if (!recommendations || recommendations.length === 0) {
    alert("이 감정에 맞는 추천 상품이 아직 없습니다.");
    return;
  }

  // 모달 생성
  const modal = document.createElement("div");
  modal.className = "modal-overlay active";
  modal.id = "emotionRecommendationModal";
  modal.innerHTML = `
    <div class="modal-content" style="max-width:1200px;width:95%;max-height:90vh;overflow-y:auto;">
      <button class="modal-close" onclick="closeEmotionRecommendation()">×</button>
      <div style="text-align:center;margin-bottom:2rem;">
        <h2 style="font-family:'Cormorant Garamond',serif;font-size:2rem;color:var(--sage);margin-bottom:.5rem;">${
          emotionData?.title || "추천 향수"
        }</h2>
        <p style="color:var(--mid);font-size:.95rem;">${
          emotionData?.desc || "이 기분에 어울리는 향기를 추천해드려요"
        }</p>
      </div>
      <div style="display:flex;flex-wrap:nowrap;gap:1.5rem;justify-content:center;align-items:stretch;padding:0.5rem 0;margin-bottom:1.5rem;overflow-x:auto;scrollbar-width:thin;">
        ${recommendations
          .map((product, idx) => {
            const productIndex =
              typeof products !== "undefined"
                ? products.findIndex((p) => p.id === product.id)
                : -1;
            const onClickHandler =
              productIndex >= 0
                ? `openProductModal(${productIndex});closeEmotionRecommendation();`
                : `window.location.href='pages/products.php';`;
            return `
          <div class="product-card" style="cursor:pointer;flex:0 0 auto;width:220px;min-width:200px;max-width:220px;" onclick="${onClickHandler}">
            <div class="product-image" style="height:220px;background:${
              product.imageUrl
                ? `url(${product.imageUrl})`
                : "linear-gradient(135deg,var(--sage-lighter),var(--sage))"
            };background-size:cover;background-position:center;border-radius:12px;">
              ${
                product.badge
                  ? `<span class="product-badge">${product.badge}</span>`
                  : ""
              }
            </div>
            <div class="product-info" style="padding:1rem 0;">
              <p class="product-brand" style="font-size:.8rem;">DewScent</p>
              <p class="product-name" style="font-size:.95rem;margin:.5rem 0;">${
                product.name
              }</p>
              <div class="product-rating" style="margin:.5rem 0;">
                <span class="stars">${"★".repeat(
                  Math.round(product.rating || 4)
                )}</span>
                <span class="rating-count" style="font-size:.8rem;">(${
                  product.reviews || 0
                })</span>
              </div>
              <p class="product-price" style="font-size:1rem;font-weight:600;color:var(--sage);">₩${(
                product.price || 0
              ).toLocaleString()}</p>
            </div>
          </div>
        `;
          })
          .join("")}
      </div>
      <div style="text-align:center;padding-top:1rem;border-top:1px solid var(--border);">
        <p style="font-size:.85rem;color:var(--light);">이 추천은 7일마다 새로운 향기로 업데이트됩니다.</p>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
  document.body.style.overflow = "hidden";
}

// 감정별 추천 닫기
function closeEmotionRecommendation() {
  const modal = document.getElementById("emotionRecommendationModal");
  if (modal) {
    modal.remove();
    document.body.style.overflow = "";
  }
}

// 감정별 추천 상품 가져오기 (7일 주기)
async function getEmotionRecommendations(emotionKey) {
  if (typeof API === "undefined" || !API.getEmotionRecommendations) {
    // API가 없으면 기본 추천 로직
    return getDefaultEmotionRecommendations(emotionKey);
  }

  return await API.getEmotionRecommendations(emotionKey);
}

// 기본 감정별 추천 (관리자 설정이 없을 때)
function getDefaultEmotionRecommendations(emotionKey) {
  const allProducts = products.filter((p) => p.status === "판매중");

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
  let filtered = allProducts.filter((p) => categories.includes(p.category));

  // 7일 주기로 다른 상품 추천 (날짜 기반 랜덤)
  const daysSinceEpoch = Math.floor(Date.now() / (1000 * 60 * 60 * 24));
  const weekCycle = Math.floor(daysSinceEpoch / 7);
  const seed = weekCycle + emotionKey.charCodeAt(0);

  // 시드 기반 셔플
  const shuffled = [...filtered].sort((a, b) => {
    const hashA = (a.id * seed) % 1000;
    const hashB = (b.id * seed) % 1000;
    return hashA - hashB;
  });

  return shuffled.slice(0, 4);
}

// 섹션 타이틀 동적 로드
function loadSectionTitles() {
  if (typeof API === "undefined" || !API.getSections) return;

  const sections = API.getSections();

  // 감정 섹션
  const emotionLabel = document.getElementById("emotionLabel");
  const emotionTitle = document.getElementById("emotionTitle");
  const emotionSubtitle = document.getElementById("emotionSubtitle");
  if (emotionLabel)
    emotionLabel.textContent = sections.emotionLabel || "FIND YOUR SCENT";
  if (emotionTitle)
    emotionTitle.textContent =
      sections.emotionTitle || "오늘, 어떤 기분인가요?";
  if (emotionSubtitle)
    emotionSubtitle.textContent =
      sections.emotionSubtitle || "감정에 맞는 향기를 추천해드릴게요";

  // 베스트 섹션
  const bestLabel = document.getElementById("bestLabel");
  const bestTitle = document.getElementById("bestTitle");
  const bestSubtitle = document.getElementById("bestSubtitle");
  const bestQuote = document.getElementById("bestQuote");
  if (bestLabel) bestLabel.textContent = sections.bestLabel || "MOST LOVED";
  if (bestTitle)
    bestTitle.textContent = sections.bestTitle || "다시 찾게 되는 향기";
  if (bestSubtitle)
    bestSubtitle.innerHTML =
      sections.bestSubtitle ||
      "한 번 스친 향기가 오래 기억에 남을 때가 있어요.<br>많은 분들이 다시 찾은 향기를 소개합니다.";
  if (bestQuote)
    bestQuote.textContent = sections.bestQuote || "— 향기는 기억을 여는 열쇠 —";
}

// 감정 섹션 및 타이틀 로드
loadEmotionSection();
loadSectionTitles();

// ───────────────────────────
// 4. 상품 그리드 렌더링
// ───────────────────────────
function renderProducts() {
  const grid = document.getElementById("productsGrid");
  if (!grid) return;

  // 관리자가 선택한 상품이 있으면 그것만, 없으면 상위 4개
  let displayProducts = products.slice(0, 4);

  if (typeof API !== "undefined" && API.getMainProductIds) {
    const selectedIds = API.getMainProductIds();
    if (selectedIds && selectedIds.length > 0) {
      // 선택된 ID에 해당하는 상품 찾기
      const filtered = products.filter((p) => selectedIds.includes(p.id));
      if (filtered.length > 0) {
        displayProducts = filtered;
      }
    }
  }

  // 최대 4개만 표시
  if (displayProducts.length > 4) {
    displayProducts = displayProducts.slice(0, 4);
  }

  grid.innerHTML = displayProducts
    .map(
      (product, index) => `
        <div class="product-card" onclick="openProductModal(${index})">
          <div class="product-image" style="position:relative;">
            ${
              product.badge
                ? `<span class="product-badge">${product.badge}</span>`
                : ""
            }
            ${
              (product.stock !== undefined && product.stock <= 0) ||
              product.status === "품절"
                ? `<div style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;border-radius:12px;z-index:1;">
                   <span style="background:var(--rose);color:#fff;padding:.5rem 1rem;border-radius:8px;font-weight:600;font-size:.9rem;">품절</span>
                 </div>`
                : ""
            }
            <button class="product-wishlist" data-id="${
              product.id
            }" onclick="event.stopPropagation();toggleWishlist(this)">${
        inWishlist(product.id) ? "♥" : "♡"
      }</button>
          </div>
          <div class="product-info">
            <p class="product-brand">DewScent</p>
            <p class="product-name">${product.name}</p>
            <div class="product-rating">
              <span class="stars">${"★".repeat(
                Math.round(product.rating)
              )}</span>
              <span class="rating-count">(${product.reviews})</span>
            </div>
            <p class="product-price">
              ₩${product.price.toLocaleString()}
              ${
                product.originalPrice
                  ? `<span class="original">₩${product.originalPrice.toLocaleString()}</span>`
                  : ""
              }
            </p>
          </div>
        </div>
      `
    )
    .join("");
}

// 공지사항/이벤트 로드
function loadNotices() {
  if (typeof API === "undefined" || !API.getActiveNotices) return;

  const notices = API.getActiveNotices();
  const section = document.getElementById("noticeSection");
  const banner = document.getElementById("noticeBanner");

  if (!section || !banner || notices.length === 0) {
    if (section) section.style.display = "none";
    return;
  }

  // 첫 번째 활성 공지/이벤트만 표시
  const notice = notices[0];
  section.style.display = "block";

  banner.innerHTML = `
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.25rem;background:var(--white);border-radius:12px;border:1px solid var(--border);box-shadow:0 2px 8px rgba(0,0,0,0.05);transition:all 0.3s ease;">
      <div style="flex:1;">
        <span style="font-size:.7rem;color:${
          notice.type === "event" ? "var(--rose)" : "var(--sage)"
        };font-weight:600;text-transform:uppercase;letter-spacing:.1em;display:inline-block;padding:.2rem .6rem;background:${
    notice.type === "event" ? "var(--rose-lighter)" : "var(--sage-bg)"
  };border-radius:4px;margin-bottom:.5rem;">${
    notice.type === "event" ? "🎁 EVENT" : "📢 NOTICE"
  }</span>
        <h3 style="font-family:'Cormorant Garamond',serif;font-size:1.1rem;color:var(--dark);margin:.5rem 0;font-weight:500;">${
          notice.title
        }</h3>
        <p style="font-size:.85rem;color:var(--mid);line-height:1.6;">${
          notice.content
        }</p>
      </div>
      ${
        notice.imageUrl
          ? `
        <div style="width:120px;height:80px;background:url(${notice.imageUrl});background-size:cover;background-position:center;border-radius:8px;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,0.1);"></div>
      `
          : ""
      }
      <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0;">
        ${
          notice.link
            ? `
          <button class="form-btn secondary" style="font-size:.85rem;padding:.5rem 1rem;" onclick="window.location.href='${notice.link}'">자세히 보기</button>
        `
            : ""
        }
        <button style="background:none;border:none;color:var(--light);cursor:pointer;font-size:1.5rem;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:all 0.2s;" onmouseover="this.style.background='var(--sage-bg)';this.style.color='var(--sage)'" onmouseout="this.style.background='none';this.style.color='var(--light)'" onclick="document.getElementById('noticeSection').style.display='none'">×</button>
      </div>
    </div>
  `;
}

// 처음 로드 시 상품 렌더링 (API에서 상품 로드 후)
(async function initProducts() {
  await loadProducts();
  renderProducts();
  initSearch();
  loadNotices();
  if (typeof renderRecentProducts === "function") {
    renderRecentProducts();
  }
})();

// ───────────────────────────
// 검색 기능
// ───────────────────────────
function initSearch() {
  const searchInput = document.querySelector(".search-input");
  const searchBtn = document.querySelector(".search-btn");

  if (!searchInput || !searchBtn) return;

  // 검색 버튼 클릭
  searchBtn.addEventListener("click", () => {
    performSearch(searchInput.value.trim());
  });

  // Enter 키 입력
  searchInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      performSearch(searchInput.value.trim());
    }
  });

  // 실시간 검색 (입력 중 자동완성)
  let searchTimeout;
  searchInput.addEventListener("input", (e) => {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    if (query.length >= 2) {
      searchTimeout = setTimeout(() => {
        showSearchSuggestions(query);
      }, 300);
    } else {
      hideSearchSuggestions();
    }
  });
}

// 검색 실행
function performSearch(query) {
  if (!query) {
    alert("검색어를 입력해주세요.");
    return;
  }

  // products.php로 이동하면서 검색어 전달
  window.location.href = `pages/products.php?search=${encodeURIComponent(
    query
  )}`;
}

// 검색 자동완성 표시
function showSearchSuggestions(query) {
  // 기존 자동완성 제거
  hideSearchSuggestions();

  if (!products || products.length === 0) return;

  // 검색어와 일치하는 상품 찾기
  const matches = products
    .filter((p) => {
      const searchLower = query.toLowerCase();
      return (
        p.name.toLowerCase().includes(searchLower) ||
        (p.desc && p.desc.toLowerCase().includes(searchLower)) ||
        (p.category && p.category.toLowerCase().includes(searchLower))
      );
    })
    .slice(0, 5); // 최대 5개만 표시

  if (matches.length === 0) return;

  // 자동완성 UI 생성
  const searchWrapper = document.querySelector(".search-wrapper");
  if (!searchWrapper) return;

  const suggestions = document.createElement("div");
  suggestions.className = "search-suggestions";
  suggestions.id = "searchSuggestions";
  suggestions.innerHTML = matches
    .map(
      (p) => `
    <div class="search-suggestion-item" onclick="selectSearchSuggestion('${
      p.name
    }')">
      <span style="font-weight:500;">${highlightMatch(p.name, query)}</span>
      <span style="font-size:.8rem;color:var(--light);">₩${p.price.toLocaleString()}</span>
    </div>
  `
    )
    .join("");

  searchWrapper.style.position = "relative";
  searchWrapper.appendChild(suggestions);
}

// 검색어 하이라이트
function highlightMatch(text, query) {
  const regex = new RegExp(`(${query})`, "gi");
  return text.replace(
    regex,
    '<mark style="background:var(--sage-bg);color:var(--sage);">$1</mark>'
  );
}

// 자동완성 숨기기
function hideSearchSuggestions() {
  const suggestions = document.getElementById("searchSuggestions");
  if (suggestions) suggestions.remove();
}

// 자동완성 항목 선택
function selectSearchSuggestion(productName) {
  const searchInput = document.querySelector(".search-input");
  if (searchInput) {
    searchInput.value = productName;
    performSearch(productName);
  }
  hideSearchSuggestions();
}

// 외부 클릭 시 자동완성 숨기기
document.addEventListener("click", (e) => {
  if (!e.target.closest(".search-wrapper")) {
    hideSearchSuggestions();
  }
});

// ───────────────────────────
// 5. 메뉴 / 모달 열고 닫기
// ───────────────────────────
function toggleMenu() {
  const sideMenu = document.getElementById("sideMenu");
  const menuOverlay = document.getElementById("menuOverlay");
  if (!sideMenu || !menuOverlay) return;

  sideMenu.classList.toggle("active");
  menuOverlay.classList.toggle("active");

  // 메뉴 열리면 메인 스크롤 잠금, 닫히면 해제
  if (sideMenu.classList.contains("active")) {
    document.body.style.overflow = "hidden";
  } else {
    document.body.style.overflow = "";
  }
}

function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;

  // 결제 모달이 열릴 때 쿠폰 정보 초기화 및 내 쿠폰 목록 로드, 저장된 주소/결제 정보 불러오기
  if (id === "checkoutModal") {
    appliedCoupon = null;
    const couponInfo = document.getElementById("couponInfo");
    const couponCode = document.getElementById("couponCode");
    if (couponInfo) couponInfo.style.display = "none";
    if (couponCode) couponCode.value = "";

    // 저장된 주소/결제 정보 불러오기
    loadSavedCheckoutInfo();

    // 내 쿠폰 목록 로드
    setTimeout(() => {
      if (typeof loadMyCouponsForCheckout === "function") {
        loadMyCouponsForCheckout();
      }
    }, 100);

    // 결제 방법에 따라 결제위젯 표시/숨김
    handlePaymentMethodChange();
  }

  modal.classList.add("active");
  document.body.style.overflow = "hidden";

  if (id === "testModal") {
    currentTestStep = 0;
    testAnswers = [];
    renderTestStep();
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;

  modal.classList.remove("active");
  document.body.style.overflow = "";
}

function closePopup() {
  const popup = document.getElementById("welcomePopup");
  if (popup) popup.classList.remove("active");
}

// 모달 영역 밖 클릭 시 닫기
document.querySelectorAll(".modal-overlay").forEach((overlay) => {
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) {
      overlay.classList.remove("active");
      document.body.style.overflow = "";
    }
  });
});

// ───────────────────────────
// 6. 향기 테스트 로직
// ───────────────────────────
function renderTestStep() {
  const body = document.getElementById("testBody");
  if (!body) return;

  if (currentTestStep >= testQuestions.length) {
    const resultType = calculateResult();
    const result = scentResults[resultType];

    body.innerHTML = `
        <div class="test-result">
          <div class="test-result-icon">DewScent</div>
          <h3>당신의 향기 타입은</h3>
          <p class="test-result-type">${result.name}</p>
          <p>${result.desc}</p>
          <p style="font-weight:500;margin-bottom:1rem">추천 제품</p>
          <div class="recommended-products">
            ${result.products
              .map((idx) => {
                const p = products[idx];
                return `
                  <div class="recommended-item" onclick="closeModal('testModal');openProductModal(${idx})">
                    <div class="recommended-item-image"></div>
                    <p class="recommended-item-name">${p.name}</p>
                    <p class="recommended-item-type">${p.type}</p>
                  </div>
                `;
              })
              .join("")}
          </div>
          <button class="form-btn primary" onclick="closeModal('testModal')">쇼핑 계속하기</button>
        </div>
      `;
    return;
  }

  const q = testQuestions[currentTestStep];

  body.innerHTML = `
      <div class="test-progress">
        ${testQuestions
          .map(
            (_, i) => `
          <div class="test-progress-bar
            ${i < currentTestStep ? "completed" : ""}
            ${i === currentTestStep ? "active" : ""}"></div>
        `
          )
          .join("")}
      </div>
  
      <div class="test-question">
        <h3>Q${currentTestStep + 1}. ${q.question}</h3>
        <p>${currentTestStep + 1} / ${testQuestions.length}</p>
      </div>
  
      <div class="test-options">
        ${q.options
          .map(
            (opt, i) => `
          <button
            class="test-option ${
              testAnswers[currentTestStep] === i ? "selected" : ""
            }"
            onclick="selectTestOption(${i})">
            ${opt}
          </button>
        `
          )
          .join("")}
      </div>
  
      <div class="test-nav">
        <button
          class="test-nav-btn prev"
          onclick="prevTestStep()"
          ${currentTestStep === 0 ? "disabled" : ""}>
          이전
        </button>
        <button class="test-nav-btn next" onclick="nextTestStep()">다음</button>
      </div>
    `;
}

function selectTestOption(index) {
  testAnswers[currentTestStep] = index;

  const opts = document.querySelectorAll(".test-option");
  opts.forEach((opt, i) => {
    opt.classList.toggle("selected", i === index);
  });
}

function nextTestStep() {
  if (testAnswers[currentTestStep] === undefined) {
    alert("답변을 선택해주세요.");
    return;
  }
  currentTestStep++;
  renderTestStep();
}

function prevTestStep() {
  if (currentTestStep > 0) {
    currentTestStep--;
    renderTestStep();
  }
}

function calculateResult() {
  const sum = testAnswers.reduce((a, b) => a + b, 0);
  if (sum <= 5) return "floral";
  if (sum <= 10) return "fresh";
  if (sum <= 15) return "woody";
  return "oriental";
}

// ───────────────────────────
// 7. 상품 상세 모달 & 리뷰
// ───────────────────────────
function openProductModal(index) {
  currentProduct = products[index];

  const nameEl = document.getElementById("productModalName");
  const priceEl = document.getElementById("productModalPrice");
  const ratingEl = document.getElementById("productModalRating");
  const descEl = document.getElementById("productModalDesc");

  if (!currentProduct || !nameEl || !priceEl || !ratingEl || !descEl) return;

  nameEl.textContent = currentProduct.name;
  priceEl.textContent = "₩" + currentProduct.price.toLocaleString();
  ratingEl.textContent = `${currentProduct.rating} (${currentProduct.reviews}개 리뷰)`;
  descEl.textContent = currentProduct.desc;

  renderReviews();
  // 상세 모달 위시리스트 버튼 상태 동기화
  const wishlistBtn = document.querySelector(
    "#productModal .wishlist-btn, .wishlist-btn"
  );
  if (wishlistBtn) {
    if (inWishlist(currentProduct.id)) {
      wishlistBtn.textContent = "♥";
      wishlistBtn.classList.add("active");
    } else {
      wishlistBtn.textContent = "♡";
      wishlistBtn.classList.remove("active");
    }
  }
  openModal("productModal");
}

async function renderReviews() {
  if (!currentProduct) return;

  // DB에서 리뷰 가져오기
  try {
    const reviews = await API.getReviews(currentProduct.id);

    // 리뷰 개수 배지 업데이트
    const badge = document.getElementById("reviewCountBadge");
    if (badge) {
      badge.textContent = `(${reviews.length})`;
    }
  } catch (err) {
    console.error("리뷰 로드 오류:", err);
    // 오류 시 LocalStorage에서 가져오기 (fallback)
    const reviews = getProductReviews(currentProduct.id);
    const badge = document.getElementById("reviewCountBadge");
    if (badge) {
      badge.textContent = `(${reviews.length})`;
    }
  }
}

async function openReviewList() {
  const container = document.getElementById("reviewListBody");
  const subtitle = document.getElementById("reviewListSubtitle");
  if (!container) return;

  if (!currentProduct) {
    container.innerHTML = `<div class="cart-empty"><p>상품을 선택해주세요.</p></div>`;
    openModal("reviewListModal");
    return;
  }

  // DB에서 리뷰 가져오기
  let reviews = [];
  try {
    reviews = await API.getReviews(currentProduct.id);
  } catch (err) {
    console.error("리뷰 로드 오류:", err);
    // 오류 시 LocalStorage에서 가져오기 (fallback)
    reviews = getProductReviews(currentProduct.id);
  }

  const user = getCurrentUser();

  if (subtitle) {
    subtitle.textContent = `${currentProduct.name} · ${reviews.length}개의 리뷰`;
  }

  if (reviews.length === 0) {
    container.innerHTML = `
      <div class="cart-empty">
        <p>아직 리뷰가 없습니다.</p>
        <p style="font-size:0.85rem;color:var(--light);margin-top:0.5rem;">첫 번째 리뷰를 남겨보세요!</p>
        <button class="form-btn primary" style="margin-top:1rem;" onclick="closeModal('reviewListModal');openReviewModal()">리뷰 작성하기</button>
      </div>
    `;
  } else {
    container.innerHTML = reviews
      .map((r) => {
        const isMyReview =
          user &&
          user.email &&
          (r.userId === user.email ||
            (user.id && r.user_id === user.id) ||
            r.user_email === user.email);
        return `
          <div class="review-item" style="position:relative;">
            <div class="review-header">
              <span class="review-user">${r.user}</span>
              <span class="review-date">${r.date}</span>
              ${
                isMyReview
                  ? `<span style="font-size:.7rem;color:var(--sage);margin-left:.5rem;">내 리뷰</span>`
                  : ""
              }
            </div>
            <div class="review-stars">
              ${"★".repeat(r.rating)}${"☆".repeat(5 - r.rating)}
            </div>
            <p class="review-content">${r.content}</p>
            ${
              isMyReview
                ? `
              <button style="position:absolute;top:.5rem;right:.5rem;background:var(--rose);color:#fff;border:none;padding:.3rem .6rem;border-radius:4px;font-size:.75rem;cursor:pointer;" onclick="deleteMyReview(${r.id}, ${currentProduct.id})">삭제</button>
            `
                : ""
            }
          </div>
        `;
      })
      .join("");

    // 리뷰 작성 버튼 추가
    container.innerHTML += `
      <div style="margin-top:1.5rem;text-align:center;">
        <button class="form-btn secondary" onclick="closeModal('reviewListModal');openReviewModal()">리뷰 작성하기</button>
      </div>
    `;
  }

  openModal("reviewListModal");
}

// 리뷰 작성 모달 열기
function openReviewModal() {
  const user = getCurrentUser();
  if (!user) {
    alert("로그인 후 리뷰를 작성할 수 있습니다.");
    openModal("loginModal");
    return;
  }

  if (!currentProduct) {
    alert("상품을 선택해주세요.");
    return;
  }

  openModal("reviewModal");
}

// 옵션(사이즈/타입) 선택
document.addEventListener("click", (e) => {
  if (
    e.target.classList.contains("option-btn") &&
    e.target.closest(".product-options")
  ) {
    const container = e.target.closest(".option-btns");
    if (!container) return;

    container.querySelectorAll(".option-btn").forEach((btn) => {
      btn.classList.remove("selected");
    });
    e.target.classList.add("selected");
  }
});

// ───────────────────────────
// 8. 장바구니 / 결제 로직
// ───────────────────────────
function addToCart() {
  // 재고 확인
  if (currentProduct.stock !== undefined && currentProduct.stock <= 0) {
    alert("품절된 상품입니다.");
    return;
  }

  const selectedSize =
    document.querySelector(".option-btn.selected.size")?.textContent || "";
  const selectedType =
    document.querySelector(".option-btn.selected.type")?.textContent || "";

  // 재고 부족 확인
  if (currentProduct.stock !== undefined && currentProduct.stock < 1) {
    alert(`재고가 부족합니다. (현재 재고: ${currentProduct.stock}개)`);
    return;
  }
  if (!currentProduct) return;

  const size =
    document.querySelector("#productSizeOptions .option-btn.selected")?.dataset
      .size || "30";
  const type =
    document.querySelector("#productTypeOptions .option-btn.selected")?.dataset
      .type || "perfume";

  const existing = cart.find(
    (item) =>
      item.id === currentProduct.id && item.size === size && item.type === type
  );

  if (existing) {
    existing.qty++;
  } else {
    cart.push({ ...currentProduct, size, type, qty: 1 });
  }

  updateCartCount();
  closeModal("productModal");
  renderCart();
  alert(currentProduct.name + "이(가) 장바구니에 담겼습니다!");
}

function buyNow() {
  // 재고 확인
  if (currentProduct.stock !== undefined && currentProduct.stock <= 0) {
    alert("품절된 상품입니다.");
    return;
  }

  if (!currentProduct) return;

  const size =
    document.querySelector("#productSizeOptions .option-btn.selected")?.dataset
      .size || "30";
  const type =
    document.querySelector("#productTypeOptions .option-btn.selected")?.dataset
      .type || "perfume";

  // 장바구니에 추가 (alert 없이)
  const existing = cart.find(
    (item) =>
      item.id === currentProduct.id && item.size === size && item.type === type
  );

  if (existing) {
    existing.qty++;
  } else {
    cart.push({ ...currentProduct, size, type, qty: 1 });
  }

  updateCartCount();
  closeModal("productModal");
  openModal("checkoutModal");
  updateCheckoutSummary();
}

function updateCartCount() {
  const total = cart.reduce((sum, item) => sum + item.qty, 0);
  const cartCount = document.getElementById("cartCount");
  if (cartCount) {
    cartCount.textContent = total;
  }
}

function renderCart() {
  const cartBody = document.getElementById("cartBody");
  if (!cartBody) return;

  // 비어 있을 때
  if (cart.length === 0) {
    cartBody.innerHTML = `
      <div class="cart-empty">
        <p>장바구니가 비어 있습니다.</p>
        <button class="form-btn ivory" onclick="closeModal('cartModal')">
          쇼핑 계속하기
        </button>
      </div>
    `;
    return;
  }

  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  const shipping = subtotal >= 50000 ? 0 : 3000;
  const total = subtotal + shipping;

  cartBody.innerHTML = `
      <div class="cart-items">
        ${cart
          .map(
            (item, index) => `
          <div class="cart-item">
            <div class="cart-item-image"></div>
            <div class="cart-item-info">
              <p class="cart-item-name">${item.name}</p>
              <p class="cart-item-option">${item.size}ml / ${item.type}</p>
              <p class="cart-item-price">₩${(
                item.price * item.qty
              ).toLocaleString()}</p>
              <div class="cart-item-qty">
                <button class="qty-btn" onclick="changeQty(${index}, -1)">−</button>
                <span>${item.qty}</span>
                <button class="qty-btn" onclick="changeQty(${index}, 1)">+</button>
              </div>
              <p class="cart-item-remove" onclick="removeFromCart(${index})">삭제</p>
            </div>
          </div>
        `
          )
          .join("")}
      </div>
  
      <div class="cart-summary">
        <div class="cart-row">
          <span>상품 금액</span>
          <span>₩${subtotal.toLocaleString()}</span>
        </div>
        <div class="cart-row">
          <span>배송비</span>
          <span>${
            shipping === 0 ? "무료" : "₩" + shipping.toLocaleString()
          }</span>
        </div>
        ${
          subtotal < 50000
            ? `<p style="font-size:.75rem;color:var(--rose);margin-top:.5rem">
                ₩${(50000 - subtotal).toLocaleString()} 추가 시 무료배송!
              </p>`
            : ""
        }
        <div class="cart-row total">
          <span>총 결제금액</span>
          <span>₩${total.toLocaleString()}</span>
        </div>
      </div>
  
      <div style="margin-top:1rem;display:flex;gap:.75rem;justify-content:flex-end;">
        <button
          class="form-btn ivory"
          onclick="closeModal('cartModal')">
          쇼핑 계속하기
        </button>
        <button
          class="form-btn primary"
          onclick="closeModal('cartModal');openModal('checkoutModal');updateCheckoutSummary();">
          결제하기
        </button>
      </div>
    `;
}

// 헤더에서 쓰기 좋은 장바구니 열기 함수
function openCart() {
  renderCart();
  openModal("cartModal");
}

function changeQty(index, delta) {
  cart[index].qty += delta;
  if (cart[index].qty <= 0) {
    cart.splice(index, 1);
  }
  updateCartCount();
  renderCart();
}

function removeFromCart(index) {
  cart.splice(index, 1);
  updateCartCount();
  renderCart();
}

// 쿠폰 적용
let appliedCoupon = null;

async function applyCouponCode() {
  const codeInput = document.getElementById("couponCode");
  const code = codeInput?.value.trim().toUpperCase();
  if (!code) {
    alert("쿠폰 코드를 입력해주세요.");
    return;
  }

  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  const result = await API.validateCoupon(code, subtotal);

  if (!result.valid) {
    alert(result.message);
    return;
  }

  appliedCoupon = result.coupon;
  const discount = API.applyCoupon(result.coupon, subtotal);

  // 쿠폰 정보 표시
  const couponInfo = document.getElementById("couponInfo");
  const couponName = document.getElementById("couponName");
  if (couponInfo && couponName) {
    couponInfo.style.display = "block";
    couponName.textContent = `${
      result.coupon.name
    } (-₩${discount.toLocaleString()})`;
  }

  updateCheckoutSummary();
  if (codeInput) codeInput.value = "";
  if (typeof loadMyCouponsForCheckout === "function") {
    loadMyCouponsForCheckout(); // 목록 새로고침
  }
}

function removeCoupon() {
  appliedCoupon = null;
  const couponInfo = document.getElementById("couponInfo");
  const couponCode = document.getElementById("couponCode");
  if (couponInfo) couponInfo.style.display = "none";
  if (couponCode) couponCode.value = "";
  updateCheckoutSummary();
  loadMyCouponsForCheckout(); // 목록 새로고침
}

function updateCheckoutSummary() {
  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);

  // 쿠폰 할인 적용
  let discount = 0;
  if (appliedCoupon) {
    discount = API.applyCoupon(appliedCoupon, subtotal);
  }

  const discountRow = document.getElementById("couponDiscountRow");
  const discountSpan = document.getElementById("checkoutDiscount");
  if (discountRow && discountSpan) {
    if (discount > 0) {
      discountRow.style.display = "flex";
      discountSpan.textContent = `-₩${discount.toLocaleString()}`;
    } else {
      discountRow.style.display = "none";
    }
  }

  const shipping = subtotal >= 50000 ? 0 : 3000;
  const total = Math.max(0, subtotal - discount + shipping);

  const subtotalEl = document.getElementById("checkoutSubtotal");
  const shippingEl = document.getElementById("checkoutShipping");
  const totalEl = document.getElementById("checkoutTotal");

  if (!subtotalEl || !shippingEl || !totalEl) return;

  subtotalEl.textContent = "₩" + subtotal.toLocaleString();
  shippingEl.textContent =
    shipping === 0 ? "무료" : "₩" + shipping.toLocaleString();
  totalEl.textContent = "₩" + total.toLocaleString();

  // 결제위젯 금액 업데이트
  updatePaymentWidgetAmount(total);

  // 결제 방법에 따라 결제위젯 표시/숨김
  handlePaymentMethodChange();
}

// 결제위젯 초기화
async function initializePaymentWidget(clientKey) {
  if (paymentWidgetInitialized && paymentWidgets) {
    return paymentWidgets;
  }

  // 토스페이먼츠 SDK 로드 대기 (최대 3초)
  let retryCount = 0;
  const maxRetries = 30; // 3초 (100ms * 30)

  while (typeof TossPayments === "undefined" && retryCount < maxRetries) {
    await new Promise((resolve) => setTimeout(resolve, 100));
    retryCount++;
  }

  // 토스페이먼츠 SDK 확인
  if (typeof TossPayments === "undefined") {
    console.error(
      "[Payment Widget] ❌ 토스페이먼츠 v2 SDK가 로드되지 않았습니다."
    );
    console.error("[Payment Widget] 전역 객체 확인:", {
      TossPayments: typeof TossPayments,
      windowTossPayments: typeof window.TossPayments,
      Payment: typeof Payment,
      windowKeys: Object.keys(window).filter(
        (k) =>
          k.toLowerCase().includes("toss") ||
          k.toLowerCase().includes("payment") ||
          k.toLowerCase().includes("widget")
      ),
    });
    return null;
  }

  // TossPayments가 함수인지 확인
  if (typeof TossPayments !== "function") {
    console.error(
      "[Payment Widget] ❌ TossPayments가 함수가 아닙니다:",
      typeof TossPayments,
      TossPayments
    );
    return null;
  }

  console.log(
    "[Payment Widget] ✅ TossPayments SDK 확인됨, 타입:",
    typeof TossPayments
  );

  // TossPayments.ANONYMOUS 확인
  if (typeof TossPayments.ANONYMOUS === "undefined") {
    console.warn(
      "[Payment Widget] ⚠️ TossPayments.ANONYMOUS가 정의되지 않았습니다."
    );
  }

  try {
    // 토스페이먼츠 초기화
    console.log(
      "[Payment Widget] TossPayments 초기화 시작, clientKey:",
      clientKey ? clientKey.substring(0, 10) + "..." : "없음"
    );
    const tossPayments = TossPayments(clientKey);

    // tossPayments 객체 확인
    const tossPaymentsKeys = tossPayments ? Object.keys(tossPayments) : [];
    console.log("[Payment Widget] TossPayments 객체:", {
      type: typeof tossPayments,
      hasWidgets: typeof tossPayments?.widgets === "function",
      keys: tossPaymentsKeys,
      hasBrandpay: typeof tossPayments?.brandpay === "function",
      hasPayment: typeof tossPayments?.payment === "function",
    });

    if (!tossPayments) {
      console.error("[Payment Widget] ❌ TossPayments 초기화 실패: null 반환");
      return null;
    }

    if (typeof tossPayments.widgets !== "function") {
      console.error(
        "[Payment Widget] ❌ tossPayments.widgets가 함수가 아닙니다:",
        {
          widgetsType: typeof tossPayments.widgets,
          tossPaymentsKeys: tossPaymentsKeys,
          availableMethods: tossPaymentsKeys.filter(
            (k) => typeof tossPayments[k] === "function"
          ),
          clientKeyPrefix: clientKey ? clientKey.substring(0, 8) : "없음",
        }
      );

      // 클라이언트 키 타입 확인 및 안내
      console.error(
        "[Payment Widget] ⚠️ 중요: 결제위젯을 사용하려면 '결제위젯 연동 키(WidgetClientKey)'가 필요합니다."
      );
      console.error(
        "[Payment Widget] ⚠️ 현재 사용 중인 키:",
        clientKey ? clientKey.substring(0, 20) + "..." : "없음"
      );
      console.error(
        "[Payment Widget] ⚠️ 토스페이먼츠 개발자센터 > API 키 메뉴에서 '결제위젯 연동 키'를 확인하세요."
      );
      console.error(
        "[Payment Widget] ⚠️ 'API 개별 연동 키'를 사용하면 widgets 메서드가 없습니다."
      );

      // 사용 가능한 메서드 안내
      const availableMethods = tossPaymentsKeys.filter(
        (k) => typeof tossPayments[k] === "function"
      );
      if (availableMethods.length > 0) {
        console.warn(
          "[Payment Widget] ⚠️ 현재 키로 사용 가능한 메서드:",
          availableMethods
        );
        if (availableMethods.includes("payment")) {
          console.warn(
            "[Payment Widget] 💡 'payment' 메서드가 있으므로 결제창 방식으로 변경할 수 있습니다."
          );
        }
      }

      // 사용 가능한 메서드가 있는지 확인
      if (tossPaymentsKeys.length > 0) {
        console.warn(
          "[Payment Widget] ⚠️ 사용 가능한 메서드:",
          tossPaymentsKeys.filter((k) => typeof tossPayments[k] === "function")
        );
      }

      return null;
    }

    // 고객 키 생성 (로그인 사용자 이메일 또는 임시 ID)
    const currentUser = getCurrentUser();
    let customerKey;
    if (currentUser && currentUser.email) {
      // 로그인 사용자: 이메일을 기반으로 고객 키 생성 (안전하게 해시 처리하는 것을 권장)
      customerKey = `customer_${currentUser.email.replace(
        /[^a-zA-Z0-9]/g,
        "_"
      )}`;
    } else {
      // 비회원: 익명 고객 키 사용
      if (typeof TossPayments.ANONYMOUS === "undefined") {
        console.error(
          "[Payment Widget] ❌ TossPayments.ANONYMOUS가 정의되지 않았습니다"
        );
        // UUID 생성 (간단한 방법)
        customerKey =
          "anonymous_" +
          Math.random().toString(36).substring(2, 15) +
          Math.random().toString(36).substring(2, 15);
      } else {
        customerKey = TossPayments.ANONYMOUS;
      }
    }

    console.log("[Payment Widget] customerKey:", customerKey);

    // 결제위젯 인스턴스 생성
    console.log("[Payment Widget] widgets() 호출 시작...");
    paymentWidgets = tossPayments.widgets({
      customerKey: customerKey,
    });

    if (!paymentWidgets) {
      console.error("[Payment Widget] ❌ widgets() 호출 결과가 null입니다");
      return null;
    }

    console.log("[Payment Widget] ✅ widgets() 호출 성공");

    paymentWidgetInitialized = true;
    console.log("[Payment Widget] ✅ 결제위젯 초기화 완료");
    return paymentWidgets;
  } catch (error) {
    console.error("[Payment Widget] ❌ 결제위젯 초기화 실패:", error);
    console.error("[Payment Widget] 에러 상세:", {
      message: error.message,
      stack: error.stack,
      name: error.name,
    });
    return null;
  }
}

// 결제위젯 금액 업데이트
async function updatePaymentWidgetAmount(amount) {
  if (!paymentWidgets || !paymentWidgetInitialized) {
    return;
  }

  try {
    await paymentWidgets.setAmount({
      currency: "KRW",
      value: amount,
    });
  } catch (error) {
    console.error("결제위젯 금액 업데이트 실패:", error);
  }
}

// 결제위젯 렌더링
async function renderPaymentWidget(clientKey) {
  console.log(
    "[Payment Widget] 렌더링 시작, clientKey:",
    clientKey ? clientKey.substring(0, 10) + "..." : "없음"
  );

  // 모달이 열려있는지 확인
  const checkoutModal = document.getElementById("checkoutModal");
  if (!checkoutModal || !checkoutModal.classList.contains("active")) {
    console.error("[Payment Widget] ❌ checkoutModal이 열려있지 않습니다.");
    return null;
  }

  // DOM 요소 찾기 (최대 3번 재시도)
  let widgetContainer = document.getElementById("tossPaymentWidget");
  let paymentMethodWidget = document.getElementById("payment-method-widget");
  let agreementWidget = document.getElementById("agreement-widget");

  // DOM 요소를 찾지 못하면 잠시 기다린 후 재시도
  if (!widgetContainer || !paymentMethodWidget || !agreementWidget) {
    console.log("[Payment Widget] DOM 요소를 찾지 못함, 재시도 중...");
    await new Promise((resolve) => setTimeout(resolve, 100));

    widgetContainer = document.getElementById("tossPaymentWidget");
    paymentMethodWidget = document.getElementById("payment-method-widget");
    agreementWidget = document.getElementById("agreement-widget");
  }

  console.log("[Payment Widget] DOM 요소 확인:", {
    widgetContainer: !!widgetContainer,
    paymentMethodWidget: !!paymentMethodWidget,
    agreementWidget: !!agreementWidget,
    modalActive: checkoutModal.classList.contains("active"),
  });

  if (!widgetContainer || !paymentMethodWidget || !agreementWidget) {
    console.error("[Payment Widget] ❌ 결제위젯 컨테이너를 찾을 수 없습니다.", {
      widgetContainer: !!widgetContainer,
      paymentMethodWidget: !!paymentMethodWidget,
      agreementWidget: !!agreementWidget,
      modalActive: checkoutModal
        ? checkoutModal.classList.contains("active")
        : false,
    });
    return null;
  }

  try {
    // 결제위젯 초기화
    console.log("[Payment Widget] 초기화 시작...");
    const widgets = await initializePaymentWidget(clientKey);
    if (!widgets) {
      console.error("[Payment Widget] ❌ 결제위젯 초기화에 실패했습니다.");
      return null;
    }
    console.log("[Payment Widget] ✅ 초기화 성공");

    // 결제 금액 계산
    const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
    const discount = appliedCoupon
      ? API.applyCoupon(appliedCoupon, subtotal)
      : 0;
    const shipping = subtotal >= 50000 ? 0 : 3000;
    const total = Math.max(0, subtotal - discount + shipping);

    console.log("[Payment Widget] 결제 금액 설정:", {
      subtotal,
      discount,
      shipping,
      total,
    });

    // 결제 금액 설정
    try {
      await widgets.setAmount({
        currency: "KRW",
        value: total,
      });
      console.log("[Payment Widget] ✅ 금액 설정 완료");
    } catch (error) {
      console.error("[Payment Widget] ❌ 금액 설정 실패:", error);
      throw error;
    }

    // 기존 위젯이 있으면 제거 (재렌더링을 위해)
    // paymentMethodWidget과 agreementWidget의 내용을 비움
    paymentMethodWidget.innerHTML = "";
    agreementWidget.innerHTML = "";

    // 결제 UI와 약관 UI 렌더링
    console.log("[Payment Widget] UI 렌더링 시작...");
    try {
      await Promise.all([
        widgets.renderPaymentMethods({
          selector: "#payment-method-widget",
          variantKey: "DEFAULT",
        }),
        widgets.renderAgreement({
          selector: "#agreement-widget",
          variantKey: "AGREEMENT",
        }),
      ]);
      console.log("[Payment Widget] ✅ UI 렌더링 완료");
    } catch (error) {
      console.error("[Payment Widget] ❌ UI 렌더링 실패:", error);
      throw error;
    }

    // 전역 변수 업데이트 (중요!)
    paymentWidgets = widgets;
    paymentWidgetInitialized = true;

    console.log("[Payment Widget] ✅ 렌더링 완료, 전역 변수 업데이트됨");
    return widgets;
  } catch (error) {
    console.error("[Payment Widget] ❌ 렌더링 실패:", error);
    console.error("[Payment Widget] 에러 상세:", {
      message: error.message,
      stack: error.stack,
      name: error.name,
    });
    // 에러 발생 시 사용자에게 알림
    const paymentMethodWidget = document.getElementById(
      "payment-method-widget"
    );
    if (paymentMethodWidget) {
      paymentMethodWidget.innerHTML =
        '<p style="color:var(--rose);font-size:0.9rem;text-align:center;padding:1rem;">결제위젯을 불러올 수 없습니다. 페이지를 새로고침해주세요.</p>';
    }
    // 초기화 실패 시 전역 변수 초기화
    paymentWidgets = null;
    paymentWidgetInitialized = false;
    return null;
  }
}

// 결제 방법 변경 처리
function handlePaymentMethodChange() {
  const paymentMethod =
    document.querySelector('#checkoutModal input[name="payment"]:checked')
      ?.value || "bank";
  const widgetContainer = document.getElementById("tossPaymentWidget");
  const bankInfo = document.getElementById("bankInfo");

  if (paymentMethod === "card") {
    // 카드 결제 선택 시 결제위젯 표시
    if (widgetContainer) {
      widgetContainer.style.display = "block";
    }
    if (bankInfo) {
      bankInfo.style.display = "none";
    }

    // 결제위젯이 아직 렌더링되지 않았으면 placeholder 메시지 표시
    if (!paymentWidgetInitialized) {
      const paymentMethodWidget = document.getElementById(
        "payment-method-widget"
      );
      const agreementWidget = document.getElementById("agreement-widget");
      if (paymentMethodWidget && paymentMethodWidget.innerHTML.trim() === "") {
        paymentMethodWidget.innerHTML =
          '<p style="color:var(--light);font-size:0.9rem;text-align:center;padding:1rem;">주문 완료 버튼을 클릭하면 결제수단을 선택할 수 있습니다.</p>';
      }
    }
  } else {
    // 무통장 입금 선택 시 결제위젯 숨김
    if (widgetContainer) {
      widgetContainer.style.display = "none";
    }
    if (bankInfo) {
      bankInfo.style.display = "block";
    }
  }
}

// 결제 방법 변경 이벤트 리스너 추가
document.addEventListener("DOMContentLoaded", () => {
  // 결제 방법 라디오 버튼 변경 감지
  const paymentRadios = document.querySelectorAll(
    '#checkoutModal input[name="payment"]'
  );
  paymentRadios.forEach((radio) => {
    radio.addEventListener("change", () => {
      handlePaymentMethodChange();
    });
  });
});

async function completeOrder() {
  // 주문 정보 수집
  const name = document
    .querySelector('#checkoutModal input[placeholder*="받으시는 분 이름"]')
    ?.value.trim();
  const phone = document
    .querySelector('#checkoutModal input[placeholder*="010"]')
    ?.value.trim();
  const address = document
    .querySelector('#checkoutModal input[placeholder*="배송"]')
    ?.value.trim();
  const paymentMethod =
    document.querySelector('#checkoutModal input[name="payment"]:checked')
      ?.value || "bank";

  if (!name || !phone || !address) {
    alert("주문자 정보를 모두 입력해주세요.");
    return;
  }

  // 주소/결제 정보 저장
  saveCheckoutInfo(name, phone, address, paymentMethod);

  // 주문번호 생성 (ORD-YYYYMMDD-HHMMSS)
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  const hours = String(now.getHours()).padStart(2, "0");
  const minutes = String(now.getMinutes()).padStart(2, "0");
  const seconds = String(now.getSeconds()).padStart(2, "0");
  const orderId = `ORD-${year}${month}${day}-${hours}${minutes}${seconds}`;

  // 주문 금액 계산
  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  const discount = appliedCoupon ? API.applyCoupon(appliedCoupon, subtotal) : 0;
  const shipping = subtotal >= 50000 ? 0 : 3000;
  const total = Math.max(0, subtotal - discount + shipping);

  // 주문 정보 객체 생성 (카드 결제와 무통장 입금 모두에서 사용)
  const order = {
    id: orderId,
    items: cart.map((item) => ({
      id: item.id,
      name: item.name,
      price: item.price,
      qty: item.qty,
      size: item.size,
      type: item.type,
      imageUrl: item.imageUrl,
    })),
    customer: {
      name: name,
      phone: phone,
      address: address,
    },
    payment: {
      method: paymentMethod,
      subtotal: subtotal,
      discount: discount,
      coupon: appliedCoupon ? appliedCoupon.code : null,
      shipping: shipping,
      total: total,
    },
    status: paymentMethod === "card" ? "결제대기" : "결제대기",
    orderedAt: now.toISOString().split("T")[0],
    createdAt: now.toISOString(),
    tracking: {
      number: null, // 운송장 번호 (결제 완료 후 생성)
      carrier: "CJ대한통운",
      history: [
        {
          status: "결제대기",
          date: now.toISOString().split("T")[0],
          time: `${hours}:${minutes}`,
          message: "주문이 접수되었습니다.",
        },
      ],
    },
  };

  // 카드 결제인 경우 토스페이먼츠 결제위젯 사용
  if (paymentMethod === "card") {
    try {
      // 사용자 이메일 가져오기
      const currentUser = getCurrentUser();
      const customerEmail =
        currentUser?.email || `${name.replace(/\s+/g, "")}@dewscent.local`;

      // 주문명 생성 (상품명들 조합)
      const orderName =
        cart.length === 1
          ? cart[0].name
          : `${cart[0].name} 외 ${cart.length - 1}건`;

      // 토스페이먼츠 v2 SDK 확인
      if (typeof TossPayments === "undefined") {
        console.error(
          "[Payment] ❌ 토스페이먼츠 v2 SDK가 로드되지 않았습니다."
        );
        console.error("[Payment] 전역 객체 확인:", {
          TossPayments: typeof TossPayments,
          windowTossPayments: typeof window.TossPayments,
          Payment: typeof Payment,
        });
        alert(
          "토스페이먼츠 SDK가 로드되지 않았습니다.\n페이지를 새로고침해주세요."
        );
        return;
      }

      // TossPayments가 함수인지 확인
      if (typeof TossPayments !== "function") {
        console.error(
          "[Payment] ❌ TossPayments가 함수가 아닙니다:",
          typeof TossPayments
        );
        alert(
          "토스페이먼츠 SDK가 올바르게 로드되지 않았습니다.\n페이지를 새로고침해주세요."
        );
        return;
      }

      console.log("[Payment] ✅ TossPayments SDK 확인됨");

      // 클라이언트 키 가져오기
      console.log("[Payment] 클라이언트 키 요청 중...");
      const keyResponse = await fetch(apiUrl("/api/payments/client-key.php"), {
        method: "GET",
        credentials: "include",
      });

      const keyData = await keyResponse.json();

      if (!keyResponse.ok || !keyData.success || !keyData.clientKey) {
        console.error("[Payment] 클라이언트 키 가져오기 실패:", keyData);
        alert("결제 설정 오류가 발생했습니다. 관리자에게 문의해주세요.");
        return;
      }

      const clientKey = keyData.clientKey;
      const keyType = keyData.keyType || "unknown";
      console.log(
        "[Payment] ✅ 클라이언트 키 가져오기 성공, 키 타입:",
        keyType
      );

      // 결제 정보를 세션에 임시 저장 (결제 성공 후 주문 저장용)
      sessionStorage.setItem(
        "pending_order",
        JSON.stringify({
          orderId: orderId,
          order: order,
          total: total,
        })
      );

      // 성공/실패 URL 생성
      const baseUrl = window.location.origin;
      // 결제 완료 후 주문 완료 페이지로 이동 (order 파라미터 포함)
      const scriptPath = window.location.pathname;
      const basePath = scriptPath.substring(0, scriptPath.lastIndexOf("/"));
      const successUrl = `${baseUrl}${basePath}/index.php?order=${encodeURIComponent(
        orderId
      )}`;
      const failUrl = `${baseUrl}${basePath}/payment_fail.php`;

      // v1 Payment SDK 확인 (우선 시도)
      if (typeof Payment !== "undefined") {
        console.log("[Payment] v1 Payment SDK 사용 시도");
        try {
          const payment = Payment(clientKey);
          if (payment && typeof payment.requestPayment === "function") {
            // v1 Payment 객체의 requestPayment 사용 (ready.php 호출 불필요)
            console.log("[Payment] v1 Payment.requestPayment 호출...");
            await payment.requestPayment("카드", {
              amount: total,
              orderId: orderId,
              orderName: orderName,
              customerName: name,
              customerEmail: customerEmail,
              successUrl: successUrl,
              failUrl: failUrl,
            });

            console.log("[Payment] ✅ 결제 요청 완료 (v1 방식)");
            return;
          }
        } catch (v1Error) {
          console.error("[Payment] v1 Payment 사용 실패:", v1Error);
          // v1이 실패하면 v2 시도
        }
      }

      // v2 TossPayments SDK 사용
      const tossPayments = TossPayments(clientKey);

      // widgets 메서드가 있는지 확인 (결제위젯 연동 키인지 확인)
      if (typeof tossPayments.widgets === "function") {
        // 결제위젯 방식 사용
        console.log("[Payment] ✅ 결제위젯 방식 사용 가능");

        // 결제위젯 초기화 및 렌더링
        if (paymentWidgetInitialized && paymentWidgets) {
          try {
            await updatePaymentWidgetAmount(total);
          } catch (error) {
            console.error("결제위젯 금액 업데이트 실패, 재초기화 시도:", error);
            paymentWidgets = null;
            paymentWidgetInitialized = false;
            const widgets = await renderPaymentWidget(clientKey);
            if (!widgets) {
              console.error("결제위젯 재초기화 실패");
              alert(
                "결제 시스템 초기화에 실패했습니다. 페이지를 새로고침해주세요."
              );
              return;
            }
          }
        } else {
          console.log("[Payment] 결제위젯 처음 초기화 시작...");
          const checkoutModal = document.getElementById("checkoutModal");
          if (!checkoutModal || !checkoutModal.classList.contains("active")) {
            console.error("[Payment] ❌ checkoutModal이 열려있지 않습니다.");
            alert("주문서 모달이 열려있지 않습니다. 다시 시도해주세요.");
            return;
          }

          const widgets = await renderPaymentWidget(clientKey);
          if (!widgets) {
            console.error("[Payment] ❌ 결제위젯 렌더링 실패");
            alert(
              "결제 시스템 초기화에 실패했습니다. 페이지를 새로고침해주세요."
            );
            return;
          }
        }

        if (!paymentWidgets || !paymentWidgetInitialized) {
          console.error("[Payment] ❌ 결제위젯 초기화 상태 불일치");
          alert(
            "결제 시스템 초기화에 실패했습니다. 페이지를 새로고침해주세요."
          );
          return;
        }

        console.log("[Payment] ✅ 결제위젯 초기화 완료, 결제 요청 준비됨");

        // 결제위젯을 사용한 결제 요청
        try {
          await paymentWidgets.requestPayment({
            orderId: orderId,
            orderName: orderName,
            successUrl: successUrl,
            failUrl: failUrl,
            customerEmail: customerEmail,
            customerName: name,
            customerMobilePhone: phone.replace(/-/g, ""),
          });

          console.log("토스페이먼츠 결제위젯 결제 요청 완료");
          return;
        } catch (paymentError) {
          console.error("토스페이먼츠 결제위젯 오류:", paymentError);
          alert(
            "결제창을 열 수 없습니다: " +
              (paymentError.message || "알 수 없는 오류")
          );
          return;
        }
      } else if (typeof tossPayments.payment === "function") {
        // 결제창 방식 사용 (API 개별 연동 키인 경우)
        console.log(
          "[Payment] ⚠️ 결제위젯 연동 키가 없어 결제창 방식으로 진행합니다"
        );

        // 고객 키 생성
        const currentUser = getCurrentUser();
        let customerKey;
        if (currentUser && currentUser.email) {
          customerKey = `customer_${currentUser.email.replace(
            /[^a-zA-Z0-9]/g,
            "_"
          )}`;
        } else {
          customerKey =
            TossPayments.ANONYMOUS ||
            `anonymous_${Math.random().toString(36).substring(2, 15)}`;
        }

        const payment = tossPayments.payment({ customerKey });

        console.log("[Payment] ✅ 결제창 방식 초기화 완료");

        // 결제창을 사용한 결제 요청
        try {
          await payment.requestPayment({
            method: "CARD",
            amount: {
              currency: "KRW",
              value: total,
            },
            orderId: orderId,
            orderName: orderName,
            successUrl: successUrl,
            failUrl: failUrl,
            customerEmail: customerEmail,
            customerName: name,
            customerMobilePhone: phone.replace(/-/g, ""),
          });

          console.log("토스페이먼츠 결제창 결제 요청 완료");
          return;
        } catch (paymentError) {
          console.error("토스페이먼츠 결제창 오류:", paymentError);
          alert(
            "결제창을 열 수 없습니다: " +
              (paymentError.message || "알 수 없는 오류")
          );
          return;
        }
      } else if (typeof tossPayments.requestPayment === "function") {
        // requestPayment 메서드가 직접 있는 경우
        // v2 SDK에서는 ready.php를 호출할 필요가 없습니다
        console.log("[Payment] ⚠️ requestPayment 메서드를 직접 사용합니다");
        console.log(
          "[Payment] 사용 가능한 메서드:",
          Object.keys(tossPayments).filter(
            (k) => typeof tossPayments[k] === "function"
          )
        );

        // orderId는 클라이언트에서 생성 (v2 SDK 방식)
        // 이미 completeOrder 함수 시작 부분에서 orderId가 생성되어 있음

        // tossPayments.requestPayment 직접 호출 시도
        try {
          // v2 SDK 형식 우선 시도: requestPayment({ method: 'CARD', ... })
          console.log("[Payment] v2 형식으로 requestPayment 호출 시도...");
          await tossPayments.requestPayment({
            method: "CARD",
            amount: {
              currency: "KRW",
              value: total,
            },
            orderId: orderId,
            orderName: orderName,
            successUrl: successUrl,
            failUrl: failUrl,
            customerEmail: customerEmail,
            customerName: name,
            customerMobilePhone: phone.replace(/-/g, ""),
          });

          console.log("[Payment] ✅ 결제 요청 완료 (v2 형식)");
          return;
        } catch (v2Error) {
          console.error("[Payment] v2 형식 호출 실패:", v2Error);
          console.log("[Payment] v1 형식으로 재시도...");

          // v1 SDK 형식 시도: requestPayment('카드', params)
          try {
            await tossPayments.requestPayment("카드", {
              amount: total,
              orderId: orderId,
              orderName: orderName,
              customerName: name,
              customerEmail: customerEmail,
              successUrl: successUrl,
              failUrl: failUrl,
            });

            console.log("[Payment] ✅ 결제 요청 완료 (v1 형식)");
            return;
          } catch (v1Error) {
            console.error("[Payment] v1 형식 호출도 실패:", v1Error);
            alert(
              "결제창을 열 수 없습니다: " +
                (v1Error.message || v2Error.message || "알 수 없는 오류")
            );
            return;
          }
        }
      } else {
        // 둘 다 없는 경우
        console.error(
          "[Payment] ❌ widgets, payment, requestPayment 메서드가 모두 없습니다"
        );
        console.error(
          "[Payment] 사용 가능한 메서드:",
          Object.keys(tossPayments).filter(
            (k) => typeof tossPayments[k] === "function"
          )
        );
        alert(
          "결제 시스템을 사용할 수 없습니다.\n\n" +
            "토스페이먼츠 개발자센터에서 올바른 클라이언트 키를 확인해주세요.\n" +
            "- 결제위젯: 결제위젯 연동 키 필요\n" +
            "- 결제창: API 개별 연동 키 필요"
        );
        return;
      }
    } catch (error) {
      console.error("토스페이먼츠 결제 오류:", error);
      alert(error.message || "결제 처리 중 오류가 발생했습니다.");
      return;
    }
  }

  // 무통장 입금인 경우 기존 플로우 계속 진행
  // 쿠폰 사용 처리 (DB에 저장)
  if (appliedCoupon) {
    try {
      const discount = API.applyCoupon(appliedCoupon, subtotal);
      await fetch(apiUrl("/api/coupons.php?action=use"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({
          couponId: appliedCoupon.id,
          orderId: result.ok ? result.orderId : null,
          orderNumber: orderId,
          discountAmount: discount,
        }),
      });

      // 캐시 초기화
      clearUserCouponsCache();
    } catch (error) {
      console.error("쿠폰 사용 처리 실패:", error);
      // 쿠폰 사용 실패해도 주문은 계속 진행
    }
  }

  // DB에 주문 저장
  try {
    const orderData = {
      id: orderId,
      orderNumber: orderId,
      items: order.items,
      customer: order.customer,
      payment: order.payment,
      total: total,
    };

    const result = await API.createOrder(orderData);
    if (!result.ok) {
      console.error("주문 저장 실패:", result.message);
      alert("주문 저장 중 오류가 발생했습니다. 다시 시도해주세요.");
      return;
    }
  } catch (error) {
    console.error("주문 저장 오류:", error);
    alert("주문 저장 중 오류가 발생했습니다. 다시 시도해주세요.");
    return;
  }

  // 주문 내역에 추가 (로컬 스토리지 - 호환성 유지)
  // DB에 저장되었으므로 localStorage에는 추가하지 않음 (중복 방지)
  // DB에서 가져온 주문이 우선되므로 localStorage는 DB에 없는 주문만 보관

  // 주문 상세 정보 저장 (주문 상세 보기용)
  const ORDER_DETAILS_KEY = "dewscent_order_details";
  let orderDetails = {};
  try {
    const stored = localStorage.getItem(ORDER_DETAILS_KEY);
    if (stored) orderDetails = JSON.parse(stored);
  } catch {}
  orderDetails[orderId] = order;
  localStorage.setItem(ORDER_DETAILS_KEY, JSON.stringify(orderDetails));

  // 장바구니 비우기 및 쿠폰 초기화
  cart = [];
  appliedCoupon = null;
  updateCartCount();
  renderCart();
  closeModal("checkoutModal");

  // 쿠폰 정보 초기화
  const couponInfo = document.getElementById("couponInfo");
  const couponCode = document.getElementById("couponCode");
  if (couponInfo) couponInfo.style.display = "none";
  if (couponCode) couponCode.value = "";

  // 주문 완료 모달 표시
  showOrderCompleteModal(order);
}

// 주소/결제 정보 저장
function saveCheckoutInfo(name, phone, address, paymentMethod) {
  const CHECKOUT_INFO_KEY = "dewscent_checkout_info";
  const info = {
    name: name,
    phone: phone,
    address: address,
    paymentMethod: paymentMethod,
    savedAt: new Date().toISOString(),
  };
  localStorage.setItem(CHECKOUT_INFO_KEY, JSON.stringify(info));
}

// 저장된 주소/결제 정보 불러오기
function loadSavedCheckoutInfo() {
  const CHECKOUT_INFO_KEY = "dewscent_checkout_info";
  try {
    const stored = localStorage.getItem(CHECKOUT_INFO_KEY);
    if (!stored) return;

    const info = JSON.parse(stored);

    // 이름 입력
    const nameInput = document.querySelector(
      '#checkoutModal input[placeholder*="받으시는 분 이름"]'
    );
    if (nameInput && info.name) {
      nameInput.value = info.name;
    }

    // 연락처 입력
    const phoneInput = document.querySelector(
      '#checkoutModal input[placeholder*="010"]'
    );
    if (phoneInput && info.phone) {
      phoneInput.value = info.phone;
    }

    // 주소 입력
    const addressInput = document.querySelector(
      '#checkoutModal input[placeholder*="배송"]'
    );
    if (addressInput && info.address) {
      addressInput.value = info.address;
    }

    // 결제 방법 선택
    if (info.paymentMethod) {
      const paymentRadio = document.querySelector(
        `#checkoutModal input[name="payment"][value="${info.paymentMethod}"]`
      );
      if (paymentRadio) {
        paymentRadio.checked = true;
        // 결제 옵션 UI 업데이트
        document
          .querySelectorAll("#checkoutModal .payment-option")
          .forEach((option) => {
            option.classList.remove("selected");
          });
        if (paymentRadio.closest(".payment-option")) {
          paymentRadio.closest(".payment-option").classList.add("selected");
        }

        // 무통장 입금 정보 표시/숨김
        const bankInfo = document.getElementById("bankInfo");
        if (bankInfo) {
          bankInfo.style.display =
            info.paymentMethod === "bank" ? "block" : "none";
        }
      }
    }
  } catch (e) {
    console.error("저장된 결제 정보 불러오기 실패:", e);
  }
}

// 주문 상세 보기
async function showOrderDetail(orderId) {
  // 먼저 DB에서 최신 주문 정보 가져오기
  let order = null;
  try {
    const orders = await API.getOrders({});
    order = orders.find((o) => o.id === orderId);
  } catch (err) {
    console.error("주문 정보 로드 오류:", err);
  }

  // DB에서 찾지 못하면 localStorage에서 가져오기 (호환성)
  if (!order) {
    const ORDER_DETAILS_KEY = "dewscent_order_details";
    let orderDetails = {};
    try {
      const stored = localStorage.getItem(ORDER_DETAILS_KEY);
      if (stored) orderDetails = JSON.parse(stored);
      order = orderDetails[orderId];
    } catch {}
  }

  if (!order) {
    alert("주문 정보를 찾을 수 없습니다.");
    return;
  }

  // 배송 추적 시뮬레이션 실행
  if (typeof API !== "undefined" && API.simulateShipping) {
    API.simulateShipping(orderId);
  }

  const subtitle = document.getElementById("orderDetailSubtitle");
  const body = document.getElementById("orderDetailBody");
  if (!subtitle || !body) {
    alert(
      `주문번호: ${orderId}\n총 결제금액: ₩${(
        order.payment?.total ||
        order.total ||
        0
      ).toLocaleString()}`
    );
    return;
  }

  subtitle.textContent = `주문번호: ${orderId}`;

  // 주문 상품 정보 가져오기
  let orderItems = order.items || [];
  if (!orderItems.length && order.id) {
    // DB에서 주문 상품 정보 가져오기 (필요시)
    try {
      const ORDER_DETAILS_KEY = "dewscent_order_details";
      const stored = localStorage.getItem(ORDER_DETAILS_KEY);
      if (stored) {
        const orderDetails = JSON.parse(stored);
        const localOrder = orderDetails[orderId];
        if (localOrder && localOrder.items) {
          orderItems = localOrder.items;
        }
      }
    } catch {}
  }

  body.innerHTML = `
    <div style="background:var(--sage-bg);padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
      <p style="font-weight:600;color:var(--sage);margin-bottom:.5rem;">주문 상태</p>
      <p style="font-size:1.1rem;color:var(--mid);"><span class="status-badge ${
        order.status === "결제완료" ||
        order.status === "paid" ||
        order.status === "배송완료" ||
        order.status === "delivered"
          ? "answered"
          : order.status === "배송준비중" ||
            order.status === "preparing" ||
            order.status === "배송중" ||
            order.status === "shipping"
          ? "answered"
          : order.status === "취소" || order.status === "cancelled"
          ? "waiting"
          : order.status === "취소요청" || order.status === "cancel_requested"
          ? "waiting"
          : "waiting"
      }">${order.status || "결제대기"}</span></p>
      <p style="font-size:.85rem;color:var(--light);margin-top:.5rem;">주문일: ${
        order.orderedAt || order.createdAt || ""
      }</p>
    </div>
    
    <div class="checkout-section" style="margin-bottom:1.5rem;">
      <p class="checkout-section-title">주문 상품</p>
      <div style="display:flex;flex-direction:column;gap:.75rem;">
        ${
          orderItems.length > 0
            ? orderItems
                .map(
                  (item) => `
          <div style="display:flex;gap:1rem;padding:.75rem;background:var(--sage-bg);border-radius:8px;">
            <div style="width:80px;height:80px;background:${
              item.imageUrl || item.image
                ? `url(${item.imageUrl || item.image})`
                : "linear-gradient(135deg,var(--sage-lighter),var(--sage))"
            };background-size:cover;background-position:center;border-radius:8px;flex-shrink:0;"></div>
            <div style="flex:1;">
              <p style="font-weight:500;margin-bottom:.25rem;">${
                item.name || item.product_name || ""
              }</p>
              <p style="font-size:.85rem;color:var(--light);margin-bottom:.25rem;">${
                item.size || ""
              } ${item.type || ""}</p>
              <p style="font-size:.9rem;color:var(--mid);">수량: ${
                item.qty || item.quantity || 1
              }개</p>
              <p style="font-size:1rem;color:var(--sage);font-weight:600;margin-top:.25rem;">₩${(
                (item.price || 0) * (item.qty || item.quantity || 1)
              ).toLocaleString()}</p>
            </div>
          </div>
        `
                )
                .join("")
            : '<p style="text-align:center;color:var(--light);padding:1rem;">주문 상품 정보를 불러올 수 없습니다.</p>'
        }
      </div>
    </div>
    
    <div class="checkout-section" style="margin-bottom:1.5rem;">
      <p class="checkout-section-title">배송 정보</p>
      <div style="background:var(--sage-bg);padding:1rem;border-radius:8px;">
        <p style="margin-bottom:.5rem;"><strong>받으시는 분:</strong> ${
          order.customer?.name ||
          order.customer_name ||
          order.shipping_name ||
          ""
        }</p>
        <p style="margin-bottom:.5rem;"><strong>연락처:</strong> ${
          order.customer?.phone ||
          order.customer_phone ||
          order.shipping_phone ||
          ""
        }</p>
        <p><strong>주소:</strong> ${
          order.customer?.address ||
          order.customer_address ||
          order.shipping_address ||
          ""
        }</p>
      </div>
    </div>
    
    <div class="checkout-section" style="margin-bottom:1.5rem;">
      <p class="checkout-section-title">결제 정보</p>
      <div class="cart-row">
        <span>상품 금액</span>
        <span>₩${(
          (order.payment?.subtotal || order.total || 0) -
          (order.payment?.shipping || 3000)
        ).toLocaleString()}</span>
      </div>
      <div class="cart-row">
        <span>배송비</span>
        <span>${
          (order.payment?.shipping || 3000) === 0
            ? "무료"
            : "₩" + (order.payment?.shipping || 3000).toLocaleString()
        }</span>
      </div>
      <div class="cart-row total">
        <span>총 결제금액</span>
        <span>₩${(
          order.payment?.total ||
          order.total ||
          0
        ).toLocaleString()}</span>
      </div>
      <p style="font-size:.85rem;color:var(--light);margin-top:.5rem;">결제 방법: ${
        order.payment?.method === "bank" ? "무통장 입금" : "카드 결제"
      }</p>
    </div>
    
    ${
      order.payment.method === "bank" && order.status === "결제대기"
        ? `
    <div style="background:var(--rose-bg, #f5ebe8);padding:1rem;border-radius:8px;margin-bottom:1.5rem;border:1px solid var(--rose-lighter, #f8dde1);">
      <p style="font-weight:600;color:var(--rose);margin-bottom:.5rem;">입금 계좌 안내</p>
      <p style="font-size:.9rem;color:var(--mid);margin-bottom:.25rem;">신한은행 110-123-456789</p>
      <p style="font-size:.9rem;color:var(--mid);">예금주: (주)듀센트</p>
    </div>
    `
        : ""
    }
    
    ${
      order.status !== "결제대기" &&
      order.status !== "주문취소" &&
      order.tracking
        ? `
    <div class="checkout-section" style="margin-bottom:1.5rem;">
      <p class="checkout-section-title">배송 추적</p>
      <div style="background:var(--sage-bg);padding:1rem;border-radius:8px;">
        ${
          order.tracking.number
            ? `
          <div style="margin-bottom:1rem;">
            <p style="font-size:.85rem;color:var(--light);margin-bottom:.25rem;">운송장 번호</p>
            <p style="font-size:1.1rem;font-weight:600;color:var(--sage);">${
              order.tracking.number
            }</p>
            <p style="font-size:.85rem;color:var(--light);margin-top:.25rem;">${
              order.tracking.carrier || "CJ대한통운"
            }</p>
          </div>
        `
            : ""
        }
        <div style="margin-top:1rem;">
          <p style="font-size:.85rem;color:var(--light);margin-bottom:.75rem;">배송 현황</p>
          ${
            order.tracking.history
              ? order.tracking.history
                  .map(
                    (h, idx) => `
            <div style="display:flex;gap:1rem;margin-bottom:.75rem;position:relative;${
              idx < order.tracking.history.length - 1
                ? "padding-bottom:.75rem;border-left:2px solid var(--border);margin-left:.5rem;padding-left:1rem;"
                : ""
            }">
              <div style="width:8px;height:8px;background:${
                idx === order.tracking.history.length - 1
                  ? "var(--sage)"
                  : "var(--border)"
              };border-radius:50%;position:absolute;left:-4px;top:4px;"></div>
              <div style="flex:1;">
                <p style="font-weight:500;color:var(--mid);margin-bottom:.25rem;">${
                  h.message
                }</p>
                <p style="font-size:.75rem;color:var(--light);">${h.date} ${
                      h.time || ""
                    }</p>
              </div>
            </div>
          `
                  )
                  .join("")
              : ""
          }
        </div>
        ${
          order.tracking.number
            ? `
          <button class="form-btn secondary" style="margin-top:1rem;width:100%;" onclick="window.open('https://www.cjlogistics.com/ko/tool/parcel/tracking?gnbInvcNo=${order.tracking.number}', '_blank')">배송 조회하기</button>
        `
            : ""
        }
      </div>
    </div>
    `
        : ""
    }
    
    <div style="display:flex;gap:.75rem;">
      <button class="form-btn ivory" style="flex:1;" onclick="closeModal('orderDetailModal')">닫기</button>
      ${
        (order.status === "결제대기" || order.status === "pending") &&
        !(order.cancelRequested === true || order.cancelRequested === 1)
          ? `<button class="form-btn secondary" style="flex:1;" onclick="cancelOrder('${orderId}')">주문 취소</button>`
          : ""
      }
      ${
        (order.status === "결제완료" ||
          order.status === "paid" ||
          order.status === "배송준비중" ||
          order.status === "preparing") &&
        !(order.cancelRequested === true || order.cancelRequested === 1)
          ? `<button class="form-btn secondary" style="flex:1;" onclick="cancelOrder('${orderId}')">주문 취소 요청</button>`
          : ""
      }
      ${
        (order.cancelRequested === true || order.cancelRequested === 1) &&
        order.status !== "취소" &&
        order.status !== "cancelled"
          ? `<div style="padding:0.75rem;background:var(--rose-bg);border-radius:8px;text-align:center;color:var(--rose);font-size:0.9rem;">⚠ 취소 요청 중입니다. 관리자 승인을 기다리고 있습니다.</div>`
          : ""
      }
      ${
        order.status === "배송완료"
          ? `<button class="form-btn secondary" style="flex:1;" onclick="requestReturnExchange('${orderId}')">반품/교환 신청</button>`
          : ""
      }
    </div>
  `;

  openModal("orderDetailModal");
}

// 반품/교환 신청
function requestReturnExchange(orderId) {
  const ORDER_DETAILS_KEY = "dewscent_order_details";
  let orderDetails = {};
  try {
    const stored = localStorage.getItem(ORDER_DETAILS_KEY);
    if (stored) orderDetails = JSON.parse(stored);
  } catch {}

  const order = orderDetails[orderId];
  if (!order) {
    alert("주문 정보를 찾을 수 없습니다.");
    return;
  }

  // 반품/교환 신청 모달 열기
  openReturnExchangeModal(order);
}

// 주문 취소 요청
async function cancelOrder(orderId) {
  // 먼저 주문 상태 확인
  let order = null;
  try {
    const orders = await API.getOrders({});
    order = orders.find((o) => o.id === orderId);
  } catch (err) {
    console.error("주문 정보 로드 오류:", err);
  }

  const isPending =
    order && (order.status === "결제대기" || order.status === "pending");
  const confirmMsg = isPending
    ? "정말 주문을 취소하시겠습니까?\n취소 후 복구할 수 없습니다."
    : "정말 주문 취소를 요청하시겠습니까?\n관리자 승인 후 취소됩니다.";

  if (!confirm(confirmMsg)) return;

  const reason = prompt("취소 사유를 입력해주세요 (선택사항):");

  try {
    const result = await API.requestOrderCancel(orderId, reason || "");
    if (result.ok) {
      alert(
        result.message ||
          (isPending
            ? "주문이 취소되었습니다."
            : "취소 요청이 접수되었습니다. 관리자 승인 후 처리됩니다.")
      );
      closeModal("orderDetailModal");
      // 주문 목록 새로고침 (DB에서 최신 상태 가져오기)
      mypageCurrentTab = "orders";
      await renderMyPage();
    } else {
      alert("취소 요청 실패: " + (result.message || "알 수 없는 오류"));
    }
  } catch (error) {
    console.error("주문 취소 요청 오류:", error);
    let errorMsg = error.message || "알 수 없는 오류";
    // JSON 파싱 오류인 경우 더 명확한 메시지 표시
    if (
      errorMsg.includes("Unexpected token") ||
      errorMsg.includes("not valid JSON")
    ) {
      errorMsg = "서버 응답 오류가 발생했습니다. 잠시 후 다시 시도해주세요.";
    }
    alert("취소 요청 중 오류가 발생했습니다: " + errorMsg);
  }
}

// 페이지 로드 시 주문 완료 모달 표시 및 주문 저장
document.addEventListener("DOMContentLoaded", function () {
  const urlParams = new URLSearchParams(window.location.search);
  const orderId = urlParams.get("order");
  const paymentKey = urlParams.get("paymentKey");
  const amount = urlParams.get("amount");

  if (orderId) {
    // sessionStorage에서 주문 정보 가져오기
    const pendingOrderData = sessionStorage.getItem("pending_order");

    if (pendingOrderData) {
      try {
        const data = JSON.parse(pendingOrderData);
        const order = data.order;

        if (order) {
          // 주문 정보를 서버에 저장
          fetch(apiUrl("/api/orders.php"), {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            credentials: "include",
            body: JSON.stringify({
              orderNumber: order.id,
              items: order.items,
              customer: order.customer,
              payment: {
                ...order.payment,
                method: "card", // 카드 결제
              },
              total: order.payment.total,
            }),
          })
            .then((response) => response.json())
            .then((result) => {
              if (result.ok) {
                console.log("[Order] ✅ 주문이 DB에 저장되었습니다:", result);
                // sessionStorage에서 제거
                sessionStorage.removeItem("pending_order");

                // 주문 정보 가져오기
                return fetch(
                  apiUrl(
                    `/api/orders.php?orderNumber=${encodeURIComponent(orderId)}`
                  ),
                  {
                    credentials: "include",
                  }
                );
              } else {
                console.error("[Order] 주문 저장 실패:", result.message);
                throw new Error(result.message || "주문 저장 실패");
              }
            })
            .then((response) => response.json())
            .then((orders) => {
              if (orders && orders.length > 0) {
                const savedOrder = orders[0];
                // 주문 완료 모달 표시
                showOrderCompleteModal(savedOrder);
                // URL에서 파라미터 제거
                const newUrl = window.location.pathname;
                window.history.replaceState({}, "", newUrl);
              } else {
                console.log("[Order] 주문 정보를 찾을 수 없습니다:", orderId);
              }
            })
            .catch((error) => {
              console.error("[Order] 주문 처리 오류:", error);
              // 오류가 발생해도 주문 완료 모달은 표시
              if (order) {
                showOrderCompleteModal(order);
              }
            });
        } else {
          console.error("[Order] sessionStorage에 주문 정보가 없습니다");
        }
      } catch (error) {
        console.error("[Order] sessionStorage 파싱 오류:", error);
      }
    } else {
      // sessionStorage에 주문 정보가 없으면 DB에서 조회
      fetch(
        apiUrl(`/api/orders.php?orderNumber=${encodeURIComponent(orderId)}`),
        {
          credentials: "include",
        }
      )
        .then((response) => response.json())
        .then((orders) => {
          if (orders && orders.length > 0) {
            const order = orders[0];
            // 주문 완료 모달 표시
            showOrderCompleteModal(order);
            // URL에서 파라미터 제거
            const newUrl = window.location.pathname;
            window.history.replaceState({}, "", newUrl);
          } else {
            console.log("[Order] 주문 정보를 찾을 수 없습니다:", orderId);
          }
        })
        .catch((error) => {
          console.error("주문 정보 가져오기 실패:", error);
        });
    }
  }
});

// 주문 완료 모달 표시
function showOrderCompleteModal(order) {
  const body = document.getElementById("orderCompleteBody");
  if (!body) {
    // 모달이 없으면 alert로 표시
    const paymentMethod =
      order.payment?.method === "card" ? "카드 결제" : "무통장 입금";
    alert(
      `결제가 완료되었습니다!\n\n주문번호: ${order.id}\n총 결제금액: ₩${(
        order.payment?.total ||
        order.total ||
        0
      ).toLocaleString()}\n결제 수단: ${paymentMethod}`
    );
    return;
  }

  // orderCompleteModal 열기
  const modal = document.getElementById("orderCompleteModal");
  if (modal) {
    modal.classList.add("active");
  }

  body.innerHTML = `
    <div style="background:linear-gradient(135deg,var(--sage-bg),#f5ebe8);padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;text-align:center;">
      <div style="font-size:2rem;margin-bottom:.5rem;">✓</div>
      <h3 style="color:var(--sage);font-size:1.2rem;margin-bottom:.5rem;">주문이 정상적으로 접수되었습니다</h3>
      <p style="font-size:.9rem;color:var(--mid);">주문번호: <strong style="color:var(--sage);">${
        order.id
      }</strong></p>
    </div>
    
    <div class="checkout-section" style="margin-bottom:1.5rem;">
      <p class="checkout-section-title">주문 상품</p>
      <div style="display:flex;flex-direction:column;gap:.75rem;">
        ${order.items
          .map(
            (item) => `
          <div style="display:flex;gap:1rem;padding:.75rem;background:var(--sage-bg);border-radius:8px;">
            <div style="width:60px;height:60px;background:${
              item.imageUrl
                ? `url(${item.imageUrl})`
                : "linear-gradient(135deg,var(--sage-lighter),var(--sage))"
            };background-size:cover;background-position:center;border-radius:8px;flex-shrink:0;"></div>
            <div style="flex:1;">
              <p style="font-weight:500;margin-bottom:.25rem;">${item.name}</p>
              <p style="font-size:.85rem;color:var(--light);">${
                item.size || ""
              } ${item.type || ""}</p>
              <p style="font-size:.85rem;color:var(--mid);margin-top:.25rem;">수량: ${
                item.qty
              }개 · ₩${(item.price * item.qty).toLocaleString()}</p>
            </div>
          </div>
        `
          )
          .join("")}
      </div>
    </div>
    
    <div class="checkout-section" style="margin-bottom:1.5rem;">
      <p class="checkout-section-title">배송 정보</p>
      <div style="background:var(--sage-bg);padding:1rem;border-radius:8px;">
        <p style="margin-bottom:.5rem;"><strong>받으시는 분:</strong> ${
          order.customer?.name ||
          order.customer_name ||
          order.shipping_name ||
          ""
        }</p>
        <p style="margin-bottom:.5rem;"><strong>연락처:</strong> ${
          order.customer?.phone ||
          order.customer_phone ||
          order.shipping_phone ||
          ""
        }</p>
        <p><strong>주소:</strong> ${
          order.customer?.address ||
          order.customer_address ||
          order.shipping_address ||
          ""
        }</p>
      </div>
    </div>
    
    <div class="checkout-section" style="margin-bottom:1.5rem;">
      <p class="checkout-section-title">결제 정보</p>
      <div class="cart-row">
        <span>상품 금액</span>
        <span>₩${order.payment.subtotal.toLocaleString()}</span>
      </div>
      ${
        order.payment.discount > 0
          ? `
      <div class="cart-row">
        <span>할인 금액</span>
        <span style="color:var(--rose);">-₩${order.payment.discount.toLocaleString()}</span>
      </div>
      ${
        order.payment.coupon
          ? `<p style="font-size:.75rem;color:var(--light);margin-top:.25rem;">쿠폰: ${order.payment.coupon}</p>`
          : ""
      }
      `
          : ""
      }
      <div class="cart-row">
        <span>배송비</span>
        <span>${
          order.payment.shipping === 0
            ? "무료"
            : "₩" + order.payment.shipping.toLocaleString()
        }</span>
      </div>
      <div class="cart-row total">
        <span>총 결제금액</span>
        <span>₩${order.payment.total.toLocaleString()}</span>
      </div>
    </div>
    
    ${
      order.payment.method === "bank"
        ? `
    <div style="background:var(--rose-bg, #f5ebe8);padding:1rem;border-radius:8px;margin-bottom:1.5rem;border:1px solid var(--rose-lighter, #f8dde1);">
      <p style="font-weight:600;color:var(--rose);margin-bottom:.5rem;">입금 계좌 안내</p>
      <p style="font-size:.9rem;color:var(--mid);margin-bottom:.25rem;">신한은행 110-123-456789</p>
      <p style="font-size:.9rem;color:var(--mid);margin-bottom:.5rem;">예금주: (주)듀센트</p>
      <p style="font-size:.8rem;color:var(--light);">• 주문 후 24시간 이내 입금이 확인되지 않으면 자동 취소될 수 있습니다.</p>
      <p style="font-size:.8rem;color:var(--light);">• 입금 확인 후 순차적으로 발송됩니다.</p>
    </div>
    `
        : ""
    }
    
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
      <button class="form-btn ivory" style="flex:1;" onclick="closeModal('orderCompleteModal');openMypageTab('orders');">주문내역 보기</button>
      <button class="form-btn primary" style="flex:1;" onclick="closeModal('orderCompleteModal');window.location.href='index.php';">쇼핑 계속하기</button>
    </div>
  `;

  openModal("orderCompleteModal");
}

// 결제 수단 선택
document.querySelectorAll(".payment-option").forEach((opt) => {
  opt.addEventListener("click", () => {
    document.querySelectorAll(".payment-option").forEach((o) => {
      o.classList.remove("selected");
    });
    opt.classList.add("selected");

    const bankInfo = document.getElementById("bankInfo");
    if (!bankInfo) return;

    bankInfo.style.display =
      opt.querySelector("input").value === "bank" ? "block" : "none";
  });
});

// ───────────────────────────
// 9. 위시리스트 / 리뷰 / 로그인
// ───────────────────────────
const WISHLIST_KEY = "ds_wishlist";

function getWishlist() {
  try {
    const raw = localStorage.getItem(WISHLIST_KEY);
    const arr = raw ? JSON.parse(raw) : [];
    return Array.isArray(arr) ? arr : [];
  } catch {
    return [];
  }
}
function setWishlist(list) {
  localStorage.setItem(WISHLIST_KEY, JSON.stringify(list || []));
}
function inWishlist(productId) {
  const list = getWishlist();
  return list.includes(productId);
}
function addToWishlist(productId) {
  const list = getWishlist();
  if (!list.includes(productId)) {
    list.push(productId);
    setWishlist(list);
  }
}
function removeFromWishlist(productId) {
  const list = getWishlist().filter((id) => id !== productId);
  setWishlist(list);
}

function toggleWishlist(btn) {
  const id = parseInt(btn.dataset.id || "0", 10);
  if (!id) return;
  if (inWishlist(id)) {
    removeFromWishlist(id);
    btn.textContent = "♡";
    btn.classList.remove("active");
  } else {
    addToWishlist(id);
    btn.textContent = "♥";
    btn.classList.add("active");
  }
  // 제품 모달 하트도 동기화
  syncModalWishlist(id);
}

// 제품 모달 하트 버튼 동기화
function syncModalWishlist(productId) {
  if (currentProduct && currentProduct.id === productId) {
    const modalBtn = document.querySelector("#productModal .wishlist-btn");
    if (modalBtn) {
      if (inWishlist(productId)) {
        modalBtn.textContent = "♥";
        modalBtn.classList.add("active");
      } else {
        modalBtn.textContent = "♡";
        modalBtn.classList.remove("active");
      }
    }
  }
}

function toggleProductWishlist(btn) {
  if (!currentProduct) return;
  const id = currentProduct.id;
  if (inWishlist(id)) {
    removeFromWishlist(id);
    btn.textContent = "♡";
    btn.classList.remove("active");
  } else {
    addToWishlist(id);
    btn.textContent = "♥";
    btn.classList.add("active");
  }
  // 제품 카드의 하트도 동기화
  syncCardWishlist(id);
}

// 제품 카드 하트 버튼 동기화
function syncCardWishlist(productId) {
  const cardBtn = document.querySelector(
    `.product-wishlist[data-id="${productId}"]`
  );
  if (cardBtn) {
    if (inWishlist(productId)) {
      cardBtn.textContent = "♥";
      cardBtn.classList.add("active");
    } else {
      cardBtn.textContent = "♡";
      cardBtn.classList.remove("active");
    }
  }
}

// 위시리스트 렌더/열기
function renderWishlist() {
  const body = document.getElementById("wishlistBody");
  if (!body) return;
  const ids = getWishlist();
  if (!ids.length) {
    body.innerHTML = `
      <div class="cart-empty">
        <p>위시리스트가 비어 있습니다.</p>
        <button class="form-btn secondary btn-compact" onclick="closeModal('wishlistModal')">닫기</button>
      </div>
    `;
    return;
  }
  const items = products.filter((p) => ids.includes(p.id));
  if (!items.length) {
    body.innerHTML = `
      <div class="cart-empty">
        <p>위시리스트가 비어 있습니다.</p>
        <button class="form-btn secondary btn-compact" onclick="closeModal('wishlistModal')">닫기</button>
      </div>
    `;
    return;
  }
  body.innerHTML = `
    <div class="cart-items">
      ${items
        .map(
          (p) => `
        <div class="cart-item">
          <div class="cart-item-info" style="display:flex;justify-content:space-between;gap:1rem;align-items:center">
            <div>
              <p class="cart-item-name">${p.name}</p>
              <p class="cart-item-price">₩${p.price.toLocaleString()}</p>
            </div>
            <div style="display:flex;gap:.5rem">
              <button class="form-btn secondary btn-compact" onclick="removeFromWishlist(${
                p.id
              });renderWishlist()">삭제</button>
              <button class="form-btn primary btn-compact" onclick="openProductFromWishlist(${
                p.id
              })">보기</button>
            </div>
          </div>
        </div>
      `
        )
        .join("")}
    </div>
  `;
}

function openWishlist() {
  renderWishlist();
  openModal("wishlistModal");
}

function openProductFromWishlist(id) {
  const index = products.findIndex((p) => p.id === id);
  if (index >= 0) {
    openProductModal(index);
  }
}

let selectedRating = 5;

function setRating(rating) {
  selectedRating = rating;
}

function submitReview() {
  const user = getCurrentUser();
  if (!user) {
    alert("로그인 후 리뷰를 작성할 수 있습니다.");
    closeModal("reviewModal");
    openModal("loginModal");
    return;
  }

  if (!currentProduct) {
    alert("상품을 선택해주세요.");
    return;
  }

  const ratingEl = document.querySelector(
    '#reviewModal input[name="rating"]:checked'
  );
  const contentEl = document.getElementById("reviewContent");

  if (!ratingEl) {
    alert("별점을 선택해주세요.");
    return;
  }

  const content = contentEl?.value.trim();
  if (!content || content.length < 10) {
    alert("리뷰 내용을 10자 이상 입력해주세요.");
    return;
  }

  const rating = parseInt(ratingEl.value);

  // DB API로 리뷰 저장
  API.createReview(currentProduct.id, {
    rating,
    content,
  })
    .then((result) => {
      if (result.ok) {
        // 입력 필드 초기화
        if (contentEl) contentEl.value = "";
        document
          .querySelectorAll('#reviewModal input[name="rating"]')
          .forEach((r) => (r.checked = false));

        alert("리뷰가 등록되었습니다. 감사합니다!");
        closeModal("reviewModal");

        // 리뷰 목록 갱신
        renderReviews();

        // 상품 정보 새로고침 (평점 업데이트)
        if (typeof loadProducts === "function") {
          loadProducts();
        }
      } else {
        alert(result.message || "리뷰 등록 중 오류가 발생했습니다.");
      }
    })
    .catch((err) => {
      console.error("리뷰 등록 오류:", err);
      alert("리뷰 등록 중 오류가 발생했습니다.");
    });
}

// ───────────────────────────
// 9. 사용자 인증 로직 (MySQL 백엔드 사용)
// ───────────────────────────
const USER_KEY = "ds_current_user";

function apiUrl(path) {
  const base = (window.DS_BASE_URL || "").replace(/\/$/, "");
  return path.startsWith("/") ? `${base}${path}` : `${base}/${path}`;
}

// 아래 함수들은 MySQL 백엔드를 사용하므로 더 이상 사용되지 않음
// (주석 처리 - 필요시 참고용으로 유지)
/*
const USERS_DB_KEY = "ds_users_db"; // 회원 목록 저장 (LocalStorage - 사용 안 함)

function getUsersDB() {
  try {
    const raw = localStorage.getItem(USERS_DB_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

function setUsersDB(users) {
  localStorage.setItem(USERS_DB_KEY, JSON.stringify(users));
}

function findUserByEmail(email) {
  const users = getUsersDB();
  return users.find((u) => u.email.toLowerCase() === email.toLowerCase());
}

function registerUser(name, email, password) {
  const users = getUsersDB();
  const newUser = {
    id: Date.now(),
    name,
    email: email.toLowerCase(),
    password,
    createdAt: new Date().toISOString().split("T")[0],
  };
  users.push(newUser);
  setUsersDB(users);
  return newUser;
}
*/

function getCurrentUser() {
  try {
    const raw = localStorage.getItem(USER_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

function setCurrentUser(user) {
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

function clearCurrentUser() {
  localStorage.removeItem(USER_KEY);
}

// 마이페이지 로컬 저장소 (주소/전화/결제수단)
const USER_PROFILE_OVERRIDES_KEY = "ds_profile_overrides";
const PAYMENT_METHOD_KEY = "ds_payment_method";
let mypageCurrentTab = "profile";
let mypageOrderFrom = "";
let mypageOrderTo = "";

const ORDER_ADDS_KEY = "ds_order_adds";
const ORDER_REMOVES_KEY = "ds_order_removes";

function getOrderAdds() {
  try {
    const raw = localStorage.getItem(ORDER_ADDS_KEY);
    const arr = raw ? JSON.parse(raw) : [];
    return Array.isArray(arr) ? arr : [];
  } catch {
    return [];
  }
}

function setOrderAdds(list) {
  localStorage.setItem(ORDER_ADDS_KEY, JSON.stringify(list || []));
}

function getOrderRemoves() {
  try {
    const raw = localStorage.getItem(ORDER_REMOVES_KEY);
    const arr = raw ? JSON.parse(raw) : [];
    return Array.isArray(arr) ? arr : [];
  } catch {
    return [];
  }
}

function setOrderRemoves(list) {
  localStorage.setItem(ORDER_REMOVES_KEY, JSON.stringify(list || []));
}

function getMergedOrders(baseOrders) {
  // DB에서 가져온 주문만 사용 (localStorage 캐시 제거)
  // localStorage의 주문 캐시는 더 이상 사용하지 않음
  const base = baseOrders || [];

  // 주문 ID로 정렬 (최신순)
  const sorted = [...base].sort((a, b) => {
    const ad = a.orderedAt ? new Date(a.orderedAt).getTime() : 0;
    const bd = b.orderedAt ? new Date(b.orderedAt).getTime() : 0;
    return bd - ad;
  });

  return sorted;
}

function getProfileOverrides() {
  try {
    const raw = localStorage.getItem(USER_PROFILE_OVERRIDES_KEY);
    return raw ? JSON.parse(raw) : {};
  } catch {
    return {};
  }
}

function setProfileOverrides(overrides) {
  localStorage.setItem(
    USER_PROFILE_OVERRIDES_KEY,
    JSON.stringify(overrides || {})
  );
}

function getPaymentMethod() {
  return localStorage.getItem(PAYMENT_METHOD_KEY) || "card";
}

function setPaymentMethod(method) {
  localStorage.setItem(PAYMENT_METHOD_KEY, method);
}

function mergeProfileWithOverrides(profile) {
  const overrides = getProfileOverrides();
  const merged = { ...profile };
  if (overrides.phone) merged.phone = overrides.phone;
  if (Array.isArray(overrides.addresses)) {
    const base = Array.isArray(profile.addresses) ? profile.addresses : [];
    merged.addresses = [...base, ...overrides.addresses];
  }
  return merged;
}

function openMypageTab(tab) {
  mypageCurrentTab = tab || "profile";
  renderMyPage();
}

// 휴대전화 번호 자동 포맷/검증 (KR)
function formatKoreanPhone(raw) {
  const digits = (raw || "").replace(/\D/g, "");
  if (digits.startsWith("02")) {
    // 02-XXXX-XXXX
    if (digits.length <= 2) return digits;
    if (digits.length <= 6) return digits.slice(0, 2) + "-" + digits.slice(2);
    if (digits.length <= 10)
      return (
        digits.slice(0, 2) +
        "-" +
        digits.slice(2, digits.length - 4) +
        "-" +
        digits.slice(-4)
      );
    return (
      digits.slice(0, 2) + "-" + digits.slice(2, 6) + "-" + digits.slice(6, 10)
    );
  }
  // 010/011 등 3-4-4 기본
  if (digits.length <= 3) return digits;
  if (digits.length <= 7) return digits.slice(0, 3) + "-" + digits.slice(3);
  if (digits.length <= 11)
    return (
      digits.slice(0, 3) +
      "-" +
      digits.slice(3, digits.length - 4) +
      "-" +
      digits.slice(-4)
    );
  return (
    digits.slice(0, 3) + "-" + digits.slice(3, 7) + "-" + digits.slice(7, 11)
  );
}

function isValidKoreanPhone(formatted) {
  // 허용: 02-XXX-XXXX, 02-XXXX-XXXX, 010-XXXX-XXXX, 011-XXX-XXXX 등
  return (
    /^02-\d{3,4}-\d{4}$/.test(formatted) ||
    /^0(10|11|16|17|18|19)-\d{3,4}-\d{4}$/.test(formatted)
  );
}

function handlePhoneInput(el) {
  if (!el) return;
  const formatted = formatKoreanPhone(el.value);
  el.value = formatted;
  // 실시간 오류 표시 최소화: 길이가 충분할 때만 invalid
  if (
    formatted.replace(/\D/g, "").length >= 9 &&
    !isValidKoreanPhone(formatted)
  ) {
    el.classList.add("invalid");
  } else {
    el.classList.remove("invalid");
  }
  const err = document.getElementById("mp_phone_error");
  if (err) err.style.display = "none";
}

function updateAuthUI() {
  const user = getCurrentUser();
  const loginLink = document.getElementById("loginLink");
  const signupLink = document.getElementById("signupLink");
  const mypageLink = document.getElementById("mypageLink");
  const logoutLink = document.getElementById("logoutLink");
  // 사이드바
  const sbLoginLink = document.getElementById("sbLoginLink");
  const sbSignupLink = document.getElementById("sbSignupLink");
  const sbMypageLink = document.getElementById("sbMypageLink");
  const sbLogoutLink = document.getElementById("sbLogoutLink");
  const sbDivider = document.getElementById("sbDivider");

  const isLoggedIn = !!user;
  if (loginLink) loginLink.style.display = isLoggedIn ? "none" : "";
  if (signupLink) signupLink.style.display = isLoggedIn ? "none" : "";
  if (mypageLink) mypageLink.style.display = isLoggedIn ? "" : "none";
  if (logoutLink) logoutLink.style.display = isLoggedIn ? "" : "none";

  if (sbLoginLink) sbLoginLink.style.display = isLoggedIn ? "none" : "";
  if (sbSignupLink) sbSignupLink.style.display = isLoggedIn ? "none" : "";
  if (sbDivider) sbDivider.style.display = isLoggedIn ? "none" : "";
  if (sbMypageLink) sbMypageLink.style.display = isLoggedIn ? "" : "none";
  if (sbLogoutLink) sbLogoutLink.style.display = isLoggedIn ? "" : "none";
}

function login() {
  const email = document.getElementById("loginEmail")?.value.trim();
  const password = document.getElementById("loginPassword")?.value.trim();

  if (!email || !password) {
    alert("이메일과 비밀번호를 입력해주세요.");
    return;
  }

  // 이메일 형식 확인
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    alert("올바른 이메일 형식을 입력해주세요.");
    return;
  }

  const loginUrl = apiUrl("/api/login.php");
  console.log("[Login] Request URL:", loginUrl); // 디버깅용
  const body = new URLSearchParams({ email, password });

  fetch(loginUrl, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body,
    credentials: "include",
  })
    .then(async (res) => {
      console.log("[Login] Response status:", res.status); // 디버깅용
      let data = null;
      try {
        const text = await res.text();
        console.log("[Login] Response text:", text.substring(0, 200)); // 디버깅용
        if (text) {
          data = JSON.parse(text);
        }
      } catch (e) {
        console.error("[Login] JSON parse error:", e);
        throw new Error(
          "서버 응답을 처리할 수 없습니다. 페이지를 새로고침해주세요."
        );
      }

      if (!res.ok) {
        throw new Error(
          data?.message || `로그인에 실패했습니다. (${res.status})`
        );
      }
      return data;
    })
    .then((data) => {
      if (!data || !data.ok || !data.user) {
        throw new Error(data?.message || "로그인 응답이 올바르지 않습니다.");
      }

      const user = data.user;
      setCurrentUser({
        id: user.id || 0,
        name: user.name || "",
        email: user.email || "",
        role: user.role || "user",
      });
      updateAuthUI();
      closeModal("loginModal");

      document.getElementById("loginEmail").value = "";
      document.getElementById("loginPassword").value = "";

      alert("로그인 되었습니다!");
    })
    .catch((err) => {
      console.error("[Login] Error:", err);
      if (err.name === "TypeError" && err.message.includes("fetch")) {
        alert(
          "서버에 연결할 수 없습니다. 인터넷 연결을 확인하거나 페이지를 새로고침해주세요."
        );
      } else {
        alert(err.message || "로그인 중 문제가 발생했습니다.");
      }
    });
}

function handleSignup(event) {
  if (event) {
    event.preventDefault();
  }

  const name = document.getElementById("signupName")?.value.trim();
  const email = document.getElementById("signupEmail")?.value.trim();
  const password = document.getElementById("signupPassword")?.value.trim();
  const errorEl = document.getElementById("signupError");

  // 에러 메시지 숨기기
  if (errorEl) {
    errorEl.style.display = "none";
    errorEl.textContent = "";
  }

  if (!name || !email || !password) {
    showSignupError("이름, 이메일, 비밀번호를 모두 입력해주세요.");
    return;
  }

  // 이름 길이 확인
  if (name.length < 2) {
    showSignupError("이름은 2자 이상 입력해주세요.");
    return;
  }

  // 이메일 형식 확인
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    showSignupError(
      "올바른 이메일 형식을 입력해주세요.\n예: example@email.com"
    );
    return;
  }

  // 비밀번호 길이 확인
  if (password.length < 8) {
    showSignupError("비밀번호는 8자 이상 입력해주세요.");
    return;
  }

  // AJAX로 회원가입 처리
  const signupUrl = apiUrl("/api/signup.php");
  const body = new URLSearchParams({ username: name, email, password });

  console.log("회원가입 요청:", { signupUrl, name, email });

  // 로딩 상태 표시
  const submitBtn = document.querySelector("#signupForm button[type='submit']");
  const originalText = submitBtn?.textContent;
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "처리 중...";
  }

  fetch(signupUrl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      "X-Requested-With": "XMLHttpRequest",
    },
    body,
    credentials: "include",
  })
    .then(async (res) => {
      console.log("회원가입 응답 상태:", res.status, res.statusText);
      const text = await res.text();
      console.log("회원가입 응답 본문:", text);

      let data = null;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error("JSON 파싱 오류:", e, "응답 텍스트:", text);
        // HTML 응답이 올 수도 있음 (에러 페이지 등)
        if (text.includes("<!DOCTYPE") || text.includes("<html")) {
          throw new Error(
            "서버 오류가 발생했습니다. 페이지를 새로고침해주세요."
          );
        }
        throw new Error(
          "서버 응답을 처리할 수 없습니다: " + text.substring(0, 100)
        );
      }

      // data.ok가 false이거나 HTTP 상태가 200이 아니면 에러
      if (!data || !data.ok) {
        const errorMsg =
          data?.message || `회원가입에 실패했습니다. (${res.status})`;
        throw new Error(errorMsg);
      }

      return data;
    })
    .then((data) => {
      if (data && data.ok) {
        // 성공 시 사용자 정보 저장 및 UI 업데이트
        if (data.user) {
          setCurrentUser({
            id: data.user.id,
            name: data.user.name,
            email: data.user.email,
            role: data.user.role,
          });
        }
        updateAuthUI();
        closeModal("signupModal");

        // 입력 필드 초기화
        document.getElementById("signupName").value = "";
        document.getElementById("signupEmail").value = "";
        document.getElementById("signupPassword").value = "";

        alert("회원가입이 완료되었습니다!\n자동으로 로그인되었습니다.");

        // 페이지 새로고침하여 세션 상태 동기화
        window.location.reload();
      }
    })
    .catch((err) => {
      console.error("회원가입 오류:", err);
      showSignupError(err.message || "회원가입 중 문제가 발생했습니다.");
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    });
}

function showSignupError(message) {
  const errorEl = document.getElementById("signupError");
  if (errorEl) {
    errorEl.textContent = message;
    errorEl.style.display = "block";
    // 스크롤하여 에러 메시지가 보이도록
    errorEl.scrollIntoView({ behavior: "smooth", block: "nearest" });
  } else {
    alert(message);
  }
}

// 기존 signup 함수는 호환성을 위해 유지
function signup() {
  handleSignup(null);
}

function logoutUser() {
  const logoutUrl = apiUrl("/api/logout.php");

  fetch(logoutUrl, { method: "POST", credentials: "include" })
    .catch(() => null)
    .finally(() => {
      clearCurrentUser();
      // 주문 관련 localStorage 캐시 클리어
      localStorage.removeItem(ORDER_ADDS_KEY);
      localStorage.removeItem(ORDER_REMOVES_KEY);
      localStorage.removeItem("dewscent_order_details");
      sessionStorage.removeItem("pending_order");
      updateAuthUI();
      const mypage = document.getElementById("mypageModal");
      if (mypage && mypage.classList.contains("active")) {
        closeModal("mypageModal");
      }
      alert("로그아웃 되었습니다.");
    });
}

// 회원 탈퇴
function withdrawUser() {
  const user = getCurrentUser();
  if (!user) {
    alert("로그인이 필요합니다.");
    return;
  }

  if (
    !confirm(
      "정말 탈퇴하시겠습니까?\n\n• 모든 주문 내역이 삭제됩니다.\n• 위시리스트가 초기화됩니다.\n• 문의 내역이 삭제됩니다.\n• 이 작업은 되돌릴 수 없습니다."
    )
  ) {
    return;
  }

  const confirmText = prompt("탈퇴를 확인하려면 '탈퇴합니다'를 입력해주세요.");
  if (confirmText !== "탈퇴합니다") {
    alert("입력이 일치하지 않아 탈퇴가 취소되었습니다.");
    return;
  }

  // MySQL DB에서 회원 탈퇴 처리 (API 호출)
  // TODO: 회원 탈퇴 API 엔드포인트 구현 필요
  // 현재는 로컬 스토리지만 삭제
  // 관련 데이터 삭제
  localStorage.removeItem(USER_PROFILE_OVERRIDES_KEY);
  localStorage.removeItem(PAYMENT_METHOD_KEY);
  localStorage.removeItem(WISHLIST_KEY);

  // 문의 내역에서 해당 사용자 문의 삭제
  const inquiries = JSON.parse(
    localStorage.getItem("dewscent_inquiries") || "[]"
  );
  const filteredInquiries = inquiries.filter(
    (inq) => user && user.email && inq.userId !== user.email
  );
  localStorage.setItem("dewscent_inquiries", JSON.stringify(filteredInquiries));

  // 로그아웃
  clearCurrentUser();
  updateAuthUI();
  closeModal("mypageModal");

  alert("회원 탈퇴가 완료되었습니다.\n이용해 주셔서 감사합니다.");
}

function renderMyPage() {
  const modal = document.getElementById("mypageModal");
  if (!modal) {
    console.error("마이페이지 모달을 찾을 수 없습니다.");
    alert("마이페이지 모달을 찾을 수 없습니다. 페이지를 새로고침해주세요.");
    return;
  }

  openModal("mypageModal");
  const user = getCurrentUser();

  // 모달이 실제로 열렸는지 확인
  if (!modal.classList.contains("active")) {
    console.warn("모달이 열리지 않았습니다. 다시 시도합니다.");
    setTimeout(() => {
      modal.classList.add("active");
      document.body.style.overflow = "hidden";
    }, 50);
  }

  // DOM이 준비될 때까지 약간의 지연을 두고 body 요소를 찾음
  const body = document.getElementById("mypageBody");
  if (!body) {
    console.error("마이페이지 body 요소를 찾을 수 없습니다.");
    // 약간의 지연 후 다시 시도
    setTimeout(() => {
      const retryBody = document.getElementById("mypageBody");
      if (retryBody) {
        retryBody.innerHTML =
          '<div style="text-align:center;color:var(--light);padding:1rem">초기화 중...</div>';
        renderMyPage();
      }
    }, 100);
    return;
  }

  body.innerHTML =
    '<div style="text-align:center;color:var(--light);padding:1rem">불러오는 중...</div>';

  if (!user) {
    body.innerHTML = `
      <p style="color:var(--mid);margin-bottom:1rem">마이페이지는 로그인 후 이용할 수 있습니다.</p>
      <div style="display:flex;gap:.5rem">
        <button class="form-btn primary" onclick="closeModal('mypageModal');openModal('loginModal')">로그인</button>
        <button class="form-btn secondary" onclick="closeModal('mypageModal');openModal('signupModal')">회원가입</button>
      </div>
    `;
    return;
  }

  Promise.all([
    API.getProfile().catch((err) => {
      console.error("프로필 로드 오류:", err);
      // 기본 프로필 반환
      return {
        id: user?.id || 0,
        name: user?.name || "",
        email: user?.email || "",
        addresses: [],
        joinedAt: "",
      };
    }),
    API.getOrders({ from: mypageOrderFrom, to: mypageOrderTo }).catch((err) => {
      console.error("주문 내역 로드 오류:", err);
      // 빈 주문 배열 반환
      return [];
    }),
  ])
    .then(([profile, orders]) => {
      // 디버깅: 주문 내역 로그
      console.log("[MyPage] DB에서 가져온 주문:", orders);
      console.log("[MyPage] 현재 사용자:", user);

      // 주문이 있으면 각 주문의 상세 정보 로그
      if (orders && orders.length > 0) {
        orders.forEach((order, index) => {
          console.log(`[MyPage] 주문 ${index + 1}:`, {
            id: order.id,
            orderNumber: order.id,
            status: order.status,
            total: order.total,
          });
        });
      }

      return [profile, orders];
    })
    .then(([profile, orders]) => {
      console.log("주문 내역 로드:", orders); // 디버깅용

      // localStorage 캐시 완전히 무시하고 DB 주문만 사용
      // 기존 캐시는 사용하지 않음
      const mergedProfile = mergeProfileWithOverrides(profile);
      const payMethod = getPaymentMethod();

      function tabButton(label, tab) {
        const activeClass =
          mypageCurrentTab === tab ? "mypage-tab active" : "mypage-tab";
        return `<button class="${activeClass}" onclick="openMypageTab('${tab}')">${label}</button>`;
      }

      const tabs = `
      <div class="mypage-tabs">
        ${tabButton("내 정보", "profile")}
        ${tabButton("주소/연락처", "addresses")}
        ${tabButton("결제수단", "payment")}
        ${tabButton("쿠폰", "coupons")}
        ${tabButton("주문내역", "orders")}
      </div>
    `;

      let content = "";

      if (mypageCurrentTab === "profile") {
        content = `
        <div class="form-group">
          <label class="form-label">이름</label>
          <div class="form-input" style="background:#fff">${
            mergedProfile.name || ""
          }</div>
        </div>
        <div class="form-group" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
          <button class="form-btn secondary" onclick="logoutUser(); closeModal('mypageModal');" style="width: 100%;">
            로그아웃
          </button>
        </div>
        <div class="form-group">
          <label class="form-label">이메일</label>
          <div class="form-input" style="background:#fff">${
            mergedProfile.email || ""
          }</div>
        </div>
        <button class="form-btn secondary" onclick="openMypageTab('orders')">주문내역 보기</button>
        <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--border);">
          <button class="form-btn" style="background:transparent;color:var(--rose);border:1px solid var(--rose);font-size:0.85rem;" onclick="withdrawUser()">회원 탈퇴</button>
        </div>
      `;
      }

      if (mypageCurrentTab === "addresses") {
        const phoneValue = mergedProfile.phone || "";
        const addresses = mergedProfile.addresses || [];
        content = `
        <div class="form-group">
          <label class="form-label">연락처</label>
          <div class="phone-row">
            <input
              type="tel"
              id="mp_phone"
              class="form-input"
              inputmode="tel"
              autocomplete="tel"
              placeholder="010-1234-5678"
              maxlength="13"
              value="${phoneValue}"
              oninput="handlePhoneInput(this)"
            >
            <button class="form-btn ivory btn-compact" onclick="savePhoneFromForm()">저장</button>
            <button class="form-btn secondary btn-compact" onclick="clearPhoneFromForm()">삭제</button>
          </div>
          <small class="input-help">예) 010-1234-5678</small>
          <div id="mp_phone_error" class="input-error" style="display:none">올바른 전화번호 형식이 아닙니다.</div>
        </div>
        <div class="form-group">
          <label class="form-label">배송지 추가</label>
          <div style="display:flex;flex-direction:column;gap:.5rem">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem">
              <input type="text" id="mp_addr_label" class="form-input" placeholder="예: 기본, 회사" style="font-size:0.85rem">
              <input type="text" id="mp_addr_recipient" class="form-input" placeholder="받는 분" style="font-size:0.85rem">
            </div>
            <input type="text" id="mp_addr_address" class="form-input" placeholder="주소" style="font-size:0.85rem">
            <input type="text" id="mp_addr_phone" class="form-input" placeholder="연락처" style="font-size:0.85rem">
            <button class="form-btn primary" style="width:100%;margin-top:.25rem" onclick="addAddressFromForm()">배송지 등록</button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">등록된 배송지</label>
          <div style="border:1px solid var(--border);border-radius:10px;background:#fff;overflow:hidden">
            ${
              addresses.length
                ? addresses
                    .map(
                      (a, idx) => `
              <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;padding:.75rem;${
                idx > 0 ? "border-top:1px solid var(--border);" : ""
              }">
                <div style="flex:1;min-width:0">
                  <div style="font-weight:500;font-size:.85rem;margin-bottom:.25rem">${
                    a.label
                  } · ${a.recipient}</div>
                  <div style="font-size:.8rem;color:var(--mid);word-break:break-all">${
                    a.address
                  }</div>
                  <div style="font-size:.75rem;color:var(--light);margin-top:.15rem">${
                    a.phone
                  }</div>
                </div>
                <button class="form-btn secondary btn-compact" style="flex-shrink:0;padding:.3rem .6rem;font-size:.75rem" onclick="deleteAddress(${
                  a.id
                })">삭제</button>
              </div>`
                    )
                    .join("")
                : '<div style="padding:1rem;text-align:center;color:var(--light);font-size:.85rem">등록된 배송지가 없습니다.</div>'
            }
          </div>
        </div>
      `;
      }

      if (mypageCurrentTab === "payment") {
        content = `
        <div class="form-group">
          <label class="form-label">결제 수단</label>
          <div class="payment-options" style="margin-top:.25rem">
            <label class="payment-option ${
              payMethod === "card" ? "selected" : ""
            }" style="display:flex;align-items:center;gap:.8rem">
              <input type="radio" name="mp_payment" value="card" ${
                payMethod === "card" ? "checked" : ""
              }> 카드 결제
            </label>
            <label class="payment-option ${
              payMethod === "bank" ? "selected" : ""
            }" style="display:flex;align-items:center;gap:.8rem">
              <input type="radio" name="mp_payment" value="bank" ${
                payMethod === "bank" ? "checked" : ""
              }> 무통장 입금
            </label>
          </div>
          <button class="form-btn primary" onclick="savePaymentMethodFromForm()">결제수단 저장</button>
        </div>
      `;
      }

      if (mypageCurrentTab === "coupons") {
        // 쿠폰 데이터는 비동기로 로드해야 하므로 별도 처리
        body.innerHTML = `${tabs}<div style="text-align:center;color:var(--light);padding:1rem">쿠폰을 불러오는 중...</div>`;
        Promise.all([
          API.getActiveCoupons().catch(() => []),
          getUserCoupons().catch(() => []),
        ]).then(([allCoupons, userCoupons]) => {
          renderCouponsTab(allCoupons || [], userCoupons || [], body, tabs);
        });
        return; // 쿠폰 탭은 비동기로 렌더링되므로 여기서 종료
      }

      if (mypageCurrentTab === "orders") {
        const fromVal = mypageOrderFrom || "";
        const toVal = mypageOrderTo || "";
        const filters = `
        <div class="orders-filters">
          <div class="orders-filters-left">
            <input type="date" id="mp_filter_from" class="form-input" value="${fromVal}">
            <span style="align-self:center;color:var(--light);margin:0 0.25rem;">~</span>
            <input type="date" id="mp_filter_to" class="form-input" value="${toVal}">
            <button class="form-btn ivory btn-compact" onclick="applyOrderFilter()">조회</button>
            <button class="form-btn secondary btn-compact" onclick="setQuickOrderFilter('all')">전체</button>
            <button class="form-btn secondary btn-compact" style="margin-left:0.5rem;" onclick="renderMyPage()">새로고침</button>
          </div>
        </div>
      `;
        // DB에서 가져온 주문을 우선 사용 (최신 상태 반영)
        const mergedOrders = getMergedOrders(orders);
        const groups = (mergedOrders || []).reduce((acc, o) => {
          const k = o.orderedAt || "날짜 미상";
          (acc[k] = acc[k] || []).push(o);
          return acc;
        }, {});
        const dates = Object.keys(groups).sort((a, b) => {
          const ad = new Date(a).getTime() || 0;
          const bd = new Date(b).getTime() || 0;
          return bd - ad;
        });
        content =
          dates
            .map((d) => {
              const rows = groups[d]
                .map(
                  (o) => `
              <tr style="cursor:pointer;" onclick="showOrderDetail('${o.id}')">
                <td style="padding:.6rem .8rem;border-top:1px solid var(--border);color:var(--sage);font-weight:500;">${
                  o.id
                }${
                    (o.cancelRequested === true || o.cancelRequested === 1) &&
                    o.status === "결제대기"
                      ? '<br><span style="color:var(--rose);font-size:0.75rem;">⚠ 취소요청중</span>'
                      : ""
                  }</td>
                <td style="padding:.6rem .8rem;border-top:1px solid var(--border)">₩${(
                  o.total || 0
                ).toLocaleString()}</td>
                <td style="padding:.6rem .8rem;border-top:1px solid var(--border)"><span class="status-badge ${
                  o.status === "결제완료" || o.status === "배송완료"
                    ? "answered"
                    : o.status === "배송준비중" || o.status === "배송중"
                    ? "answered"
                    : o.status === "취소"
                    ? "waiting"
                    : "waiting"
                }">${o.status || "결제대기"}</span></td>
              </tr>`
                )
                .join("");
              return `
              <div class="orders-group">
                <div class="orders-date">${d}</div>
                <div style="padding:0;border:1px solid var(--border);border-radius:12px;background:#fff;overflow:hidden">
                  <table style="width:100%;border-collapse:collapse">
                    <thead>
                      <tr style="background:var(--sage-bg)">
                        <th style="text-align:left;padding:.6rem .8rem;font-size:.85rem;color:var(--light)">주문번호</th>
                        <th style="text-align:left;padding:.6rem .8rem;font-size:.85rem;color:var(--light)">금액</th>
                        <th style="text-align:left;padding:.6rem .8rem;font-size:.85rem;color:var(--light)">상태</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${
                        rows ||
                        `<tr><td colspan="3" style="padding:1rem;text-align:center;color:var(--light)">주문 없음</td></tr>`
                      }
                    </tbody>
                  </table>
                </div>
              </div>
            `;
            })
            .join("") ||
          `<div style="padding:1rem;border:1px solid var(--border);border-radius:12px;background:#fff;color:var(--light);text-align:center;height:60px">주문 내역이 없습니다.</div>`;
        content = filters + content;
      }

      body.innerHTML = `${tabs}${content}<button class="form-btn ivory" onclick="closeModal('mypageModal')">닫기</button>`;

      // 쿠폰 탭인 경우 렌더링 확인
      if (mypageCurrentTab === "coupons") {
        console.log("쿠폰 탭 렌더링 완료");
        console.log("Content length:", content.length);
        const couponSection = body.querySelector('[style*="내 쿠폰"]');
        console.log("쿠폰 섹션 요소:", couponSection);
      }
    })
    .catch((error) => {
      console.error("마이페이지 로드 오류:", error);
      const errorBody = document.getElementById("mypageBody");
      if (errorBody) {
        errorBody.innerHTML = `
        <div style="text-align:center;padding:2rem;">
          <p style="color:var(--mid);margin-bottom:1rem;">정보를 불러오는 중 오류가 발생했습니다.</p>
          <p style="color:var(--light);font-size:0.85rem;margin-bottom:1rem;">${
            error.message || "알 수 없는 오류"
          }</p>
          <button class="form-btn primary" onclick="renderMyPage()">다시 시도</button>
          <button class="form-btn secondary" onclick="closeModal('mypageModal')" style="margin-top:.5rem;">닫기</button>
        </div>
      `;
      }
    });
}

// 마이페이지 액션 핸들러
function savePhoneFromForm() {
  const phone = document.getElementById("mp_phone")?.value.trim();
  const err = document.getElementById("mp_phone_error");
  const input = document.getElementById("mp_phone");
  if (!isValidKoreanPhone(phone)) {
    if (err) err.style.display = "";
    if (input) input.classList.add("invalid");
    return;
  }
  const overrides = getProfileOverrides();
  overrides.phone = phone || "";
  setProfileOverrides(overrides);
  alert("전화번호가 저장되었습니다.");
}

function addAddressFromForm() {
  const label = document.getElementById("mp_addr_label")?.value.trim();
  const recipient = document.getElementById("mp_addr_recipient")?.value.trim();
  const address = document.getElementById("mp_addr_address")?.value.trim();
  const phone = document.getElementById("mp_addr_phone")?.value.trim();
  if (!label || !recipient || !address || !phone) {
    alert("배송지 정보를 모두 입력해주세요.");
    return;
  }
  const overrides = getProfileOverrides();
  if (!Array.isArray(overrides.addresses)) overrides.addresses = [];
  overrides.addresses.push({
    id: Date.now(),
    label,
    recipient,
    address,
    phone,
  });
  setProfileOverrides(overrides);
  alert("배송지가 등록되었습니다.");
  openMypageTab("addresses");
}

function savePaymentMethodFromForm() {
  const el = document.querySelector("input[name='mp_payment']:checked");
  const method = el ? el.value : "card";
  setPaymentMethod(method);
  alert("결제수단이 저장되었습니다.");
}

function addOrderFromForm() {
  const id = document.getElementById("mp_order_id")?.value.trim();
  const totalStr = document.getElementById("mp_order_total")?.value.trim();
  const status = document.getElementById("mp_order_status")?.value.trim();
  const orderedAt = document.getElementById("mp_order_date")?.value;
  const total = parseInt(totalStr || "0", 10) || 0;
  if (!id || !status || !orderedAt) {
    alert("주문번호, 상태, 주문일을 입력해주세요.");
    return;
  }
  const adds = getOrderAdds();
  if (adds.some((o) => o.id === id)) {
    alert("이미 같은 주문번호가 존재합니다.");
    return;
  }
  adds.push({ id, total, status, orderedAt });
  setOrderAdds(adds);
  alert("주문이 추가되었습니다.");
  openMypageTab("orders");
}

function deleteOrder(orderId) {
  if (!orderId) return;
  // 먼저 추가된 주문에서 제거
  let adds = getOrderAdds();
  const before = adds.length;
  adds = adds.filter((o) => o.id !== orderId);
  if (adds.length !== before) {
    setOrderAdds(adds);
    openMypageTab("orders");
    return;
  }
  // 기본 주문은 removes에 기록
  const removes = getOrderRemoves();
  if (!removes.includes(orderId)) {
    removes.push(orderId);
    setOrderRemoves(removes);
  }
  openMypageTab("orders");
}

function applyOrderFilter() {
  const from = document.getElementById("mp_filter_from")?.value || "";
  const to = document.getElementById("mp_filter_to")?.value || "";
  mypageOrderFrom = from;
  mypageOrderTo = to;
  openMypageTab("orders");
}

function setQuickOrderFilter(preset) {
  const now = new Date();
  const firstDay = (y, m) => new Date(y, m, 1);
  const lastDay = (y, m) => new Date(y, m + 1, 0);
  let from = "";
  let to = "";
  if (preset === "this_month") {
    const s = firstDay(now.getFullYear(), now.getMonth());
    const e = lastDay(now.getFullYear(), now.getMonth());
    from = s.toISOString().slice(0, 10);
    to = e.toISOString().slice(0, 10);
  } else if (preset === "last_month") {
    const d = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const s = firstDay(d.getFullYear(), d.getMonth());
    const e = lastDay(d.getFullYear(), d.getMonth());
    from = s.toISOString().slice(0, 10);
    to = e.toISOString().slice(0, 10);
  } else if (preset === "this_year") {
    const s = new Date(now.getFullYear(), 0, 1);
    const e = new Date(now.getFullYear(), 11, 31);
    from = s.toISOString().slice(0, 10);
    to = e.toISOString().slice(0, 10);
  } else if (preset === "last_year") {
    const y = now.getFullYear() - 1;
    const s = new Date(y, 0, 1);
    const e = new Date(y, 11, 31);
    from = s.toISOString().slice(0, 10);
    to = e.toISOString().slice(0, 10);
  } else {
    from = "";
    to = "";
  }
  mypageOrderFrom = from;
  mypageOrderTo = to;
  openMypageTab("orders");
}
function clearPhoneFromForm() {
  const overrides = getProfileOverrides();
  overrides.phone = "";
  setProfileOverrides(overrides);
  alert("전화번호가 삭제되었습니다.");
  openMypageTab("addresses");
}

function deleteAddress(id) {
  const overrides = getProfileOverrides();
  if (!Array.isArray(overrides.addresses)) return;
  overrides.addresses = overrides.addresses.filter((a) => a.id !== id);
  setProfileOverrides(overrides);
  alert("배송지가 삭제되었습니다.");
  openMypageTab("addresses");
}

// ESC 키로 모달/사이드메뉴 닫기
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    document
      .querySelectorAll(".modal-overlay.active,.popup-overlay.active")
      .forEach((modal) => {
        modal.classList.remove("active");
      });

    const sideMenu = document.getElementById("sideMenu");
    const menuOverlay = document.getElementById("menuOverlay");
    if (sideMenu && menuOverlay) {
      sideMenu.classList.remove("active");
      menuOverlay.classList.remove("active");
    }

    document.body.style.overflow = "";
  }
});

// 페이지 로드 시 기본 장바구니 상태 렌더링
renderCart();
updateAuthUI();

// ───────────────────────────
// 15. 문의하기 기능
// ───────────────────────────
const INQUIRY_KEY = "dewscent_inquiries";

function getInquiries() {
  try {
    return JSON.parse(localStorage.getItem(INQUIRY_KEY)) || [];
  } catch {
    return [];
  }
}

function setInquiries(list) {
  localStorage.setItem(INQUIRY_KEY, JSON.stringify(list));
}

function getInquiryTypeLabel(type) {
  const labels = {
    shipping: "배송 문의",
    exchange: "교환/환불",
    product: "상품 문의",
    order: "주문/결제",
    other: "기타",
  };
  return labels[type] || "기타";
}

async function submitInquiry() {
  const user = getCurrentUser();
  if (!user) {
    alert("로그인 후 이용해주세요.");
    closeModal("inquiryModal");
    openModal("loginModal");
    return;
  }

  const type = document.getElementById("inquiryType").value;
  const orderNo = document.getElementById("inquiryOrderNo").value.trim();
  const title = document.getElementById("inquiryTitle").value.trim();
  const content = document.getElementById("inquiryContent").value.trim();

  if (!type) {
    alert("문의 유형을 선택해주세요.");
    return;
  }
  if (!title) {
    alert("제목을 입력해주세요.");
    return;
  }
  if (!content) {
    alert("문의 내용을 입력해주세요.");
    return;
  }

  try {
    const result = await API.createInquiry({
      type: type,
      orderNo: orderNo || null,
      title: title,
      content: content,
    });

    if (result.ok) {
      // 폼 초기화
      document.getElementById("inquiryType").value = "";
      document.getElementById("inquiryOrderNo").value = "";
      document.getElementById("inquiryTitle").value = "";
      document.getElementById("inquiryContent").value = "";

      alert("문의가 등록되었습니다. 영업일 기준 1~2일 내 답변드릴게요!");
      closeModal("inquiryModal");
    } else {
      alert(result.message || "문의 등록 중 오류가 발생했습니다.");
    }
  } catch (err) {
    console.error("문의 등록 오류:", err);
    alert("문의 등록 중 오류가 발생했습니다.");
  }
}

function openInquiryList() {
  const user = getCurrentUser();
  if (!user) {
    alert("로그인 후 이용해주세요.");
    openModal("loginModal");
    return;
  }

  renderInquiryList();
  openModal("inquiryListModal");
}

async function renderInquiryList() {
  const container = document.getElementById("inquiryListBody");
  if (!container) return;

  const user = getCurrentUser();
  if (!user) {
    container.innerHTML = `<div class="inquiry-empty"><p>로그인이 필요합니다.</p></div>`;
    return;
  }

  // DB에서 문의 가져오기
  let myInquiries = [];
  try {
    const allInquiries = await API.getInquiries();
    // 일반 사용자는 자신의 문의만 표시
    myInquiries = allInquiries.filter(
      (inq) =>
        (user && user.id && inq.user_id === user.id) ||
        (user && user.email && inq.userId === user.email)
    );
  } catch (err) {
    console.error("문의 로드 오류:", err);
    // 오류 시 LocalStorage에서 가져오기 (fallback)
    const allInquiries = getInquiries();
    myInquiries = allInquiries.filter(
      (inq) => user && user.email && inq.userId === user.email
    );
  }

  if (myInquiries.length === 0) {
    container.innerHTML = `
      <div class="inquiry-empty">
        <p>등록된 문의가 없습니다.</p>
        <p style="font-size:0.8rem;">궁금한 점이 있으시면 문의해주세요!</p>
      </div>
    `;
    return;
  }

  container.innerHTML = myInquiries
    .map(
      (inq) => `
    <div class="inquiry-item" data-id="${inq.id}">
      <div class="inquiry-item-header" onclick="toggleInquiryItem(${inq.id})">
        <div class="inquiry-item-left">
          <span class="inquiry-type-badge ${inq.type}">${getInquiryTypeLabel(
        inq.type
      )}</span>
          <span class="inquiry-item-title">${inq.title}</span>
        </div>
        <div style="display:flex;align-items:center;gap:0.5rem;">
          <span class="inquiry-status ${inq.status}">${
        inq.status === "answered" ? "답변완료" : "답변대기"
      }</span>
          <span class="inquiry-item-date">${inq.createdAt}</span>
        </div>
      </div>
      <div class="inquiry-item-body">
        <p class="inquiry-item-content">${inq.content.replace(
          /\n/g,
          "<br>"
        )}</p>
        ${
          inq.orderNo
            ? `<p class="inquiry-item-meta">주문번호: ${inq.orderNo}</p>`
            : ""
        }
        ${
          inq.answer
            ? `
          <div class="inquiry-answer">
            <p class="inquiry-answer-label">관리자 답변 (${
              inq.answeredAt || ""
            })</p>
            <p class="inquiry-answer-content">${inq.answer.replace(
              /\n/g,
              "<br>"
            )}</p>
          </div>
        `
            : ""
        }
      </div>
    </div>
  `
    )
    .join("");
}

function toggleInquiryItem(id) {
  const item = document.querySelector(`.inquiry-item[data-id="${id}"]`);
  if (item) {
    item.classList.toggle("open");
  }
}

function openInquiry() {
  openModal("inquiryModal");
}

// ───────────────────────────
// 16. 팝업 시스템 (일주일간 안보기 포함)
// ───────────────────────────
const POPUP_HIDE_KEY = "dewscent_popup_hidden";

function getHiddenPopups() {
  try {
    const stored = localStorage.getItem(POPUP_HIDE_KEY);
    if (!stored) return {};
    const data = JSON.parse(stored);
    const now = Date.now();
    // 만료된 것 제거
    Object.keys(data).forEach((id) => {
      if (data[id] < now) delete data[id];
    });
    localStorage.setItem(POPUP_HIDE_KEY, JSON.stringify(data));
    return data;
  } catch {
    return {};
  }
}

function hidePopupForWeek(popupId) {
  const hidden = getHiddenPopups();
  hidden[popupId] = Date.now() + 7 * 24 * 60 * 60 * 1000; // 7일 후
  localStorage.setItem(POPUP_HIDE_KEY, JSON.stringify(hidden));
}

function showSitePopups() {
  if (typeof API === "undefined" || !API.getActivePopups) return;

  try {
    const popups = API.getActivePopups();
    if (!popups || !Array.isArray(popups)) return;

    const hiddenPopups = getHiddenPopups();

    // 숨긴 팝업 제외
    const visiblePopups = popups
      .filter((p) => p && p.id && !hiddenPopups[p.id])
      .slice(0, 5); // 최대 5개

    if (visiblePopups.length === 0) return;

    // 팝업 컨테이너 생성
    let container = document.getElementById("sitePopupContainer");
    if (!container) {
      container = document.createElement("div");
      container.id = "sitePopupContainer";
      container.style.cssText =
        "position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);";
      document.body.appendChild(container);
    }

    // 첫 번째 팝업만 표시 (여러 개면 순차적으로)
    let currentPopupIndex = 0;

    function renderCurrentPopup() {
      if (currentPopupIndex >= visiblePopups.length) {
        container.remove();
        return;
      }

      const popup = visiblePopups[currentPopupIndex];
      container.innerHTML = `
      <div class="site-popup" style="background:#fff;border-radius:16px;max-width:400px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);overflow:hidden;">
        ${
          popup.imageUrl
            ? `<div style="height:200px;background:var(--sage-lighter);display:flex;align-items:center;justify-content:center;"><img src="${popup.imageUrl}" alt="" style="max-width:100%;max-height:100%;object-fit:cover;"></div>`
            : ""
        }
        <div style="padding:1.5rem;">
          <h3 style="font-family:'Cormorant Garamond',serif;font-size:1.3rem;margin-bottom:0.5rem;">${
            popup.title
          }</h3>
          ${
            popup.content
              ? `<p style="color:var(--mid);font-size:0.9rem;line-height:1.6;margin-bottom:1rem;">${popup.content}</p>`
              : ""
          }
          ${
            popup.link
              ? `<a href="${popup.link}" class="form-btn primary" style="display:block;text-align:center;margin-bottom:0.75rem;">자세히 보기</a>`
              : ""
          }
          <div style="display:flex;gap:0.5rem;margin-top:1rem;">
            <button onclick="closeCurrentPopup()" class="form-btn secondary" style="flex:1;">닫기</button>
            <button onclick="hidePopupWeek(${
              popup.id
            })" class="form-btn" style="flex:1;background:transparent;color:var(--light);border:1px solid var(--border);">일주일간 안보기</button>
          </div>
        </div>
      </div>
    `;
    }

    window.closeCurrentPopup = function () {
      currentPopupIndex++;
      renderCurrentPopup();
    };

    window.hidePopupWeek = function (id) {
      try {
        if (id) {
          hidePopupForWeek(id);
        }
        // 팝업 닫기
        currentPopupIndex++;
        if (currentPopupIndex >= visiblePopups.length) {
          // 모든 팝업을 봤으면 컨테이너 제거
          if (container && container.parentNode) {
            container.remove();
          }
        } else {
          renderCurrentPopup();
        }
      } catch (err) {
        console.error("hidePopupWeek 오류:", err);
        // 오류가 발생해도 팝업은 닫기
        if (container && container.parentNode) {
          container.remove();
        }
      }
    };

    renderCurrentPopup();
  } catch (err) {
    console.error("showSitePopups 오류:", err);
    // 오류 발생 시 팝업 컨테이너 제거
    const container = document.getElementById("sitePopupContainer");
    if (container) container.remove();
  }
}

// 페이지 로드 시 팝업 표시 (메인 페이지에서만, 인트로 후에)
if (document.querySelector(".slider-section")) {
  setTimeout(showSitePopups, 4000); // 인트로(2.5초) + 여유시간 후 팝업 표시
}

// ============================================================
// 쿠폰 관련 함수들
// ============================================================
let userCouponsCache = null;
let userCouponsCacheTime = 0;
const USER_COUPONS_CACHE_DURATION = 30000; // 30초 캐시

// 쿠폰 탭 렌더링 함수
function renderCouponsTab(allCoupons, userCoupons, body, tabs) {
  // 디버깅: 쿠폰 데이터 확인
  console.log("=== 쿠폰 탭 디버깅 ===");
  console.log("All coupons:", allCoupons);
  console.log("User coupons:", userCoupons);
  console.log("All coupons length:", allCoupons.length);
  console.log("User coupons length:", userCoupons.length);

  // 사용 가능한 쿠폰 목록
  const availableCoupons = allCoupons.filter((c) => {
    if (!c || !c.id) return false;
    // 이미 받은 쿠폰은 제외
    return !userCoupons.some((uc) => uc && uc.couponId === c.id);
  });

  console.log("Available coupons:", availableCoupons);

  // 내 쿠폰 목록 - ID 타입 변환 포함, 관리자가 삭제한 쿠폰은 제외
  const myCoupons = userCoupons
    .map((uc, index) => {
      console.log(`Processing user coupon ${index}:`, uc);
      if (!uc || uc.couponId === undefined || uc.couponId === null) {
        console.log(`  - Invalid user coupon at index ${index}`);
        return null;
      }
      // ID 타입 변환 (숫자/문자열 모두 처리)
      const couponId = Number(uc.couponId);
      const coupon = allCoupons.find((c) => {
        if (!c || !c.id) return false;
        return Number(c.id) === couponId || c.id === uc.couponId;
      });
      console.log(
        `  - Looking for coupon ID: ${
          uc.couponId
        } (${typeof uc.couponId}), converted: ${couponId}`
      );
      console.log(
        `  - All coupon IDs:`,
        allCoupons.map((c) => ({ id: c.id, type: typeof c.id }))
      );
      console.log(`  - Found coupon:`, coupon);
      // 관리자가 삭제한 쿠폰은 null 반환 (표시하지 않음)
      if (!coupon) {
        console.log(
          `  - Coupon not found (deleted by admin) for ID: ${uc.couponId}`
        );
        return null;
      }
      const merged = {
        ...coupon,
        receivedAt: uc.receivedAt,
        used: uc.used || false,
      };
      console.log(`  - Merged coupon:`, merged);
      return merged;
    })
    .filter((c) => c !== null);

  console.log("My coupons (final):", myCoupons);
  console.log("My coupons length:", myCoupons.length);

  let content = `
    <div style="margin-bottom:2rem;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
        <h3 style="font-size:.9rem;color:var(--dark);font-weight:500;">받을 수 있는 쿠폰</h3>
        <span style="font-size:.75rem;color:var(--light);">${
          availableCoupons.length
        }개</span>
      </div>
      <div style="display:flex;flex-direction:column;gap:.5rem;">
        ${
          availableCoupons.length > 0
            ? availableCoupons
                .map((coupon) => {
                  if (!coupon) return "";
                  const discountText =
                    coupon.type === "percent"
                      ? `${coupon.value || 0}%`
                      : `₩${(coupon.value || 0).toLocaleString()}`;
                  const couponName = coupon.name || "쿠폰";
                  return `
              <div style="padding:1.25rem;background:linear-gradient(135deg, var(--white) 0%, var(--sage-bg) 100%);border:1px solid var(--sage-lighter);border-radius:12px;display:flex;justify-content:space-between;align-items:stretch;gap:1.25rem;width:100%;box-sizing:border-box;box-shadow:0 2px 8px rgba(95,113,97,0.08);transition:all 0.3s;" onmouseover="this.style.borderColor='var(--sage)';this.style.boxShadow='0 4px 16px rgba(95,113,97,0.2)'" onmouseout="this.style.borderColor='var(--sage-lighter)';this.style.boxShadow='0 2px 8px rgba(95,113,97,0.08)'">
                <div style="flex:1;min-width:0;display:flex;flex-direction:column;justify-content:space-between;">
                  <div>
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;flex-wrap:wrap;">
                      <strong style="color:var(--sage);font-size:1.1rem;white-space:nowrap;font-weight:600;letter-spacing:-0.02em;">${discountText} 할인</strong>
                    </div>
                    <p style="font-weight:600;color:var(--dark);font-size:1rem;margin-bottom:.4rem;word-break:break-word;overflow-wrap:break-word;line-height:1.5;">${couponName}</p>
                    <p style="font-size:.8rem;color:var(--light);line-height:1.5;">${
                      coupon.minAmount > 0
                        ? `최소 ₩${coupon.minAmount.toLocaleString()}`
                        : "제한없음"
                    }${coupon.endDate ? ` · ~${coupon.endDate}` : ""}</p>
                  </div>
                </div>
                <div style="display:flex;align-items:center;flex-shrink:0;">
                  <button class="form-btn primary" style="padding:.65rem 1.25rem;font-size:.85rem;white-space:nowrap;border-radius:8px;background:var(--sage);color:var(--white);border:none;font-weight:500;transition:all 0.2s;" onclick="receiveCoupon(${
                    coupon.id || 0
                  })" onmouseover="this.style.background='var(--sage-hover)';this.style.transform='scale(1.05)'" onmouseout="this.style.background='var(--sage)';this.style.transform='scale(1)'">받기</button>
                </div>
              </div>
            `;
                })
                .join("")
            : '<div style="padding:1.5rem;text-align:center;color:var(--light);background:var(--sage-bg);border-radius:8px;border:1px dashed var(--border);"><p style="font-size:.8rem;">받을 수 있는 쿠폰이 없습니다</p></div>'
        }
      </div>
    </div>
    
    <div>
      ${(() => {
        // 사용 가능한 쿠폰과 사용한 쿠폰 분리
        const availableMyCoupons = myCoupons.filter((c) => !c.used);
        const usedCoupons = myCoupons.filter((c) => c.used);

        return `
          <div style="margin-bottom:2rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
              <h3 style="font-size:.9rem;color:var(--dark);font-weight:500;">사용 가능한 쿠폰</h3>
              <span style="font-size:.75rem;color:var(--light);">${
                availableMyCoupons.length
              }개</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:.5rem;">
              ${
                availableMyCoupons.length > 0
                  ? availableMyCoupons
                      .map((coupon, idx) => {
                        if (!coupon) return "";
                        const discountText =
                          coupon.type === "percent"
                            ? `${coupon.value || 0}%`
                            : `₩${(coupon.value || 0).toLocaleString()}`;
                        const couponName = coupon.name || "쿠폰";
                        const couponCode = coupon.code || "";

                        return `
                    <div style="padding:1.25rem;background:linear-gradient(135deg, var(--white) 0%, var(--sage-bg) 100%);border:1px solid var(--sage-lighter);border-radius:12px;width:100%;box-sizing:border-box;box-shadow:0 2px 8px rgba(95,113,97,0.08);transition:all 0.3s;" onmouseover="this.style.borderColor='var(--sage)';this.style.boxShadow='0 4px 16px rgba(95,113,97,0.2)'" onmouseout="this.style.borderColor='var(--sage-lighter)';this.style.boxShadow='0 2px 8px rgba(95,113,97,0.08)'">
                      <div style="display:flex;justify-content:space-between;align-items:stretch;gap:1.25rem;">
                        <div style="flex:1;min-width:0;display:flex;flex-direction:column;justify-content:space-between;">
                          <div>
                            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;flex-wrap:wrap;">
                              <strong style="color:var(--sage);font-size:1.1rem;white-space:nowrap;font-weight:600;letter-spacing:-0.02em;">${discountText} 할인</strong>
                            </div>
                            <p style="font-weight:600;color:var(--dark);font-size:1rem;margin-bottom:.4rem;word-break:break-word;overflow-wrap:break-word;line-height:1.5;">${couponName}</p>
                            ${
                              couponCode
                                ? `<p style="font-size:.8rem;color:var(--mid);margin-bottom:.3rem;line-height:1.5;">코드: <code style="font-family:monospace;color:var(--sage);background:var(--white);padding:.25rem .6rem;border-radius:6px;font-size:.8rem;white-space:nowrap;font-weight:500;border:1px solid var(--sage-lighter);">${couponCode}</code></p>`
                                : ""
                            }
                            ${
                              coupon.endDate
                                ? `<p style="font-size:.75rem;color:var(--light);margin-top:.2rem;line-height:1.5;">~ ${coupon.endDate}</p>`
                                : ""
                            }
                            ${
                              coupon.receivedAt
                                ? `<p style="font-size:.75rem;color:var(--light);margin-top:.15rem;line-height:1.5;">받은 날짜: ${coupon.receivedAt}</p>`
                                : ""
                            }
                          </div>
                        </div>
                        <div style="display:flex;align-items:center;flex-shrink:0;">
                          ${
                            couponCode
                              ? `
                            <button class="form-btn secondary" style="padding:.65rem 1.25rem;font-size:.85rem;white-space:nowrap;border-radius:8px;background:var(--sage);color:var(--white);border:none;font-weight:500;transition:all 0.2s;" onclick="event.stopPropagation();copyCouponCode('${couponCode}')" onmouseover="this.style.background='var(--sage-hover)';this.style.transform='scale(1.05)'" onmouseout="this.style.background='var(--sage)';this.style.transform='scale(1)'">복사</button>
                          `
                              : ""
                          }
                        </div>
                      </div>
                    </div>
                  `;
                      })
                      .filter((html) => html)
                      .join("")
                  : '<div style="padding:1.5rem;text-align:center;color:var(--light);background:var(--sage-bg);border-radius:8px;border:1px dashed var(--border);"><p style="font-size:.8rem;">사용 가능한 쿠폰이 없습니다</p></div>'
              }
            </div>
          </div>
          
          <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
              <h3 style="font-size:.9rem;color:var(--dark);font-weight:500;">사용한 쿠폰</h3>
              <span style="font-size:.75rem;color:var(--light);">${
                usedCoupons.length
              }개</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:.5rem;">
              ${
                usedCoupons.length > 0
                  ? usedCoupons
                      .map((coupon, idx) => {
                        if (!coupon) return "";
                        const discountText =
                          coupon.type === "percent"
                            ? `${coupon.value || 0}%`
                            : `₩${(coupon.value || 0).toLocaleString()}`;
                        const couponName = coupon.name || "쿠폰";
                        const couponCode = coupon.code || "";

                        return `
                    <div style="padding:1.25rem;background:linear-gradient(135deg, var(--sage-bg) 0%, var(--cloud) 100%);border:1px solid var(--border);border-radius:12px;opacity:0.7;width:100%;box-sizing:border-box;box-shadow:0 2px 8px rgba(95,113,97,0.08);transition:all 0.3s;" onmouseover="this.style.borderColor='var(--sage)';this.style.boxShadow='0 4px 16px rgba(95,113,97,0.2)'" onmouseout="this.style.borderColor='var(--border)';this.style.boxShadow='0 2px 8px rgba(95,113,97,0.08)'">
                      <div style="display:flex;justify-content:space-between;align-items:stretch;gap:1.25rem;">
                        <div style="flex:1;min-width:0;display:flex;flex-direction:column;justify-content:space-between;">
                          <div>
                            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;flex-wrap:wrap;">
                              <strong style="color:var(--light);font-size:1.1rem;white-space:nowrap;font-weight:600;letter-spacing:-0.02em;">${discountText} 할인</strong>
                              <span style="font-size:.7rem;color:var(--light);padding:.2rem .5rem;background:var(--border);border-radius:6px;white-space:nowrap;">사용완료</span>
                            </div>
                            <p style="font-weight:600;color:var(--dark);font-size:1rem;margin-bottom:.4rem;word-break:break-word;overflow-wrap:break-word;line-height:1.5;">${couponName}</p>
                            ${
                              couponCode
                                ? `<p style="font-size:.8rem;color:var(--mid);margin-bottom:.3rem;line-height:1.5;">코드: <code style="font-family:monospace;color:var(--sage);background:var(--white);padding:.25rem .6rem;border-radius:6px;font-size:.8rem;white-space:nowrap;font-weight:500;border:1px solid var(--sage-lighter);">${couponCode}</code></p>`
                                : ""
                            }
                            ${
                              coupon.endDate
                                ? `<p style="font-size:.75rem;color:var(--light);margin-top:.2rem;line-height:1.5;">~ ${coupon.endDate}</p>`
                                : ""
                            }
                            ${
                              coupon.receivedAt
                                ? `<p style="font-size:.75rem;color:var(--light);margin-top:.15rem;line-height:1.5;">받은 날짜: ${coupon.receivedAt}</p>`
                                : ""
                            }
                          </div>
                        </div>
                        <div style="display:flex;align-items:center;flex-shrink:0;">
                          <span style="font-size:.8rem;color:var(--light);padding:.65rem 1.25rem;white-space:nowrap;">사용완료</span>
                        </div>
                      </div>
                    </div>
                  `;
                      })
                      .filter((html) => html)
                      .join("")
                  : '<div style="padding:1.5rem;text-align:center;color:var(--light);background:var(--sage-bg);border-radius:8px;border:1px dashed var(--border);"><p style="font-size:.8rem;">사용한 쿠폰이 없습니다</p></div>'
              }
            </div>
          </div>
        `;
      })()}
    </div>
  `;

  body.innerHTML = `${tabs}${content}<button class="form-btn ivory" onclick="closeModal('mypageModal')">닫기</button>`;
}

async function getUserCoupons() {
  try {
    const now = Date.now();
    if (
      userCouponsCache &&
      now - userCouponsCacheTime < USER_COUPONS_CACHE_DURATION
    ) {
      return userCouponsCache;
    }

    const response = await fetch(apiUrl("/api/coupons.php?action=my"), {
      credentials: "include",
    });

    const data = await response.json();

    if (data.success && data.coupons) {
      // DB 필드명을 JavaScript 필드명으로 변환
      userCouponsCache = data.coupons.map((uc) => ({
        couponId: uc.coupon_id,
        receivedAt: uc.received_at ? uc.received_at.split(" ")[0] : "",
        used: uc.used == 1,
        code: uc.code,
        name: uc.name,
        type: uc.type,
        value: uc.value,
        minAmount: uc.min_amount,
        maxDiscount: uc.max_discount,
        startDate: uc.start_date || "",
        endDate: uc.end_date || "",
        active: uc.active == 1,
      }));
      userCouponsCacheTime = now;
      return userCouponsCache;
    }
    return [];
  } catch (error) {
    console.error("내 쿠폰 조회 실패:", error);
    return [];
  }
}

function setUserCoupons(coupons) {
  // DB에 저장되므로 더 이상 localStorage 사용 안 함
  // 캐시만 업데이트
  userCouponsCache = coupons;
  userCouponsCacheTime = Date.now();
}

function clearUserCouponsCache() {
  userCouponsCache = null;
  userCouponsCacheTime = 0;
}

async function receiveCoupon(couponId) {
  try {
    const response = await fetch(apiUrl("/api/coupons.php?action=receive"), {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ couponId }),
    });

    const data = await response.json();

    if (data.success) {
      // 캐시 초기화
      clearUserCouponsCache();

      // 쿠폰 정보 가져오기
      const allCoupons = await API.getActiveCoupons();
      const coupon = allCoupons.find((c) => c.id === couponId);

      if (coupon) {
        alert(`쿠폰을 받았습니다!\n\n${coupon.name}\n코드: ${coupon.code}`);
      } else {
        alert("쿠폰을 받았습니다!");
      }

      // 마이페이지 다시 렌더링
      openMypageTab("coupons");
    } else {
      alert(data.message || "쿠폰 받기에 실패했습니다.");
    }
  } catch (error) {
    console.error("쿠폰 받기 실패:", error);
    alert("쿠폰 받기 중 오류가 발생했습니다.");
  }
}

function copyCouponCode(code) {
  navigator.clipboard
    .writeText(code)
    .then(() => {
      alert(
        `쿠폰 코드가 복사되었습니다: ${code}\n\n결제 페이지에서 사용하실 수 있습니다.`
      );
    })
    .catch(() => {
      // 클립보드 복사 실패 시 대체 방법
      const textarea = document.createElement("textarea");
      textarea.value = code;
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand("copy");
      document.body.removeChild(textarea);
      alert(
        `쿠폰 코드가 복사되었습니다: ${code}\n\n결제 페이지에서 사용하실 수 있습니다.`
      );
    });
}

// 결제 모달에서 내 쿠폰 목록 표시
async function loadMyCouponsForCheckout() {
  const myCouponsList = document.getElementById("myCouponsList");
  const availableCouponsList = document.getElementById("availableCouponsList");

  if (!myCouponsList || !availableCouponsList) {
    console.log("쿠폰 리스트 요소를 찾을 수 없습니다");
    return;
  }

  try {
    const userCoupons = (await getUserCoupons()) || [];
    const allCoupons = (await API.getActiveCoupons()) || [];
    const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);

    console.log("사용자 쿠폰:", userCoupons);
    console.log("활성 쿠폰:", allCoupons);
    console.log("소계:", subtotal);

    // 사용 가능한 내 쿠폰만 필터링 - ID 타입 변환 포함, 현재 적용된 쿠폰 제외
    const availableMyCoupons = userCoupons
      .filter(
        (uc) =>
          uc && !uc.used && (!appliedCoupon || uc.couponId !== appliedCoupon.id)
      )
      .map((uc) => {
        if (!uc || uc.couponId === undefined || uc.couponId === null) {
          console.log("Invalid user coupon:", uc);
          return null;
        }
        // ID 타입 변환 (숫자/문자열 모두 처리)
        const couponId = Number(uc.couponId);
        const coupon = allCoupons.find((c) => {
          if (!c || !c.id) return false;
          return (
            Number(c.id) === couponId ||
            c.id === uc.couponId ||
            String(c.id) === String(uc.couponId)
          );
        });
        console.log(
          `Looking for coupon ID: ${
            uc.couponId
          } (${typeof uc.couponId}), found:`,
          coupon
        );
        if (!coupon) {
          console.log("Coupon not found for ID:", uc.couponId);
          return null;
        }
        // 쿠폰은 이미 활성화된 것이므로 검증 없이 반환
        const merged = { ...coupon, receivedAt: uc.receivedAt };
        console.log("Valid coupon found:", merged);
        return merged;
      })
      .filter((c) => c !== null);

    console.log("사용 가능한 쿠폰:", availableMyCoupons);

    // 항상 섹션 표시
    myCouponsList.style.display = "block";

    if (availableMyCoupons.length > 0) {
      availableCouponsList.innerHTML = availableMyCoupons
        .map((coupon, idx) => {
          if (!coupon) {
            console.log(`Coupon ${idx} is null/undefined`);
            return "";
          }
          try {
            console.log(`Rendering coupon ${idx}:`, coupon);
            const discount = API.applyCoupon(coupon, subtotal);
            const discountText =
              coupon.type === "percent"
                ? `${coupon.value || 0}%`
                : `₩${(coupon.value || 0).toLocaleString()}`;
            const couponName = coupon.name || "쿠폰";
            const couponCode = coupon.code || "";

            console.log(
              `  - Name: ${couponName}, Code: ${couponCode}, Discount: ${discountText}`
            );

            return `
            <div style="padding:1.25rem;background:linear-gradient(135deg, var(--white) 0%, var(--sage-bg) 100%);border:1px solid var(--sage-lighter);border-radius:12px;display:flex;justify-content:space-between;align-items:stretch;gap:1.25rem;cursor:pointer;transition:all 0.3s;width:100%;box-sizing:border-box;box-shadow:0 2px 8px rgba(95,113,97,0.08);" onclick="applyMyCoupon(${
              coupon.id || 0
            })" onmouseover="this.style.borderColor='var(--sage)';this.style.boxShadow='0 4px 16px rgba(95,113,97,0.2)'" onmouseout="this.style.borderColor='var(--sage-lighter)';this.style.boxShadow='0 2px 8px rgba(95,113,97,0.08)'">
              <div style="flex:1;min-width:0;display:flex;flex-direction:column;justify-content:space-between;">
                <div>
                  <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;flex-wrap:wrap;">
                    <strong style="color:var(--sage);font-size:1.1rem;white-space:nowrap;font-weight:600;letter-spacing:-0.02em;">${discountText} 할인</strong>
                  </div>
                  <p style="font-weight:600;color:var(--dark);font-size:1rem;margin-bottom:.4rem;word-break:break-word;overflow-wrap:break-word;line-height:1.5;">${couponName}</p>
                  ${
                    couponCode
                      ? `<p style="font-size:.8rem;color:var(--mid);margin-bottom:.3rem;line-height:1.5;">코드: <code style="font-family:monospace;color:var(--sage);background:var(--white);padding:.25rem .6rem;border-radius:6px;font-size:.8rem;white-space:nowrap;font-weight:500;border:1px solid var(--sage-lighter);">${couponCode}</code></p>`
                      : ""
                  }
                  <p style="font-size:.85rem;color:var(--light);margin-bottom:.2rem;line-height:1.5;">최대 <strong style="color:var(--sage);font-size:.9rem;">₩${discount.toLocaleString()}</strong> 할인</p>
                  ${
                    coupon.minAmount > 0
                      ? `<p style="font-size:.75rem;color:var(--light);line-height:1.5;">최소 주문금액: ₩${coupon.minAmount.toLocaleString()}</p>`
                      : ""
                  }
                </div>
              </div>
              <div style="display:flex;align-items:center;flex-shrink:0;">
                <button class="form-btn secondary" style="padding:.65rem 1.25rem;font-size:.85rem;white-space:nowrap;border-radius:8px;background:var(--sage);color:var(--white);border:none;font-weight:500;transition:all 0.2s;" onmouseover="this.style.background='var(--sage-hover)';this.style.transform='scale(1.05)'" onmouseout="this.style.background='var(--sage)';this.style.transform='scale(1)'">적용</button>
              </div>
            </div>
          `;
          } catch (e) {
            console.error("쿠폰 렌더링 오류:", e, coupon);
            return "";
          }
        })
        .filter((html) => html && html.trim())
        .join("");
    } else {
      availableCouponsList.innerHTML =
        '<div style="padding:1.5rem;text-align:center;color:var(--light);background:var(--sage-bg);border-radius:8px;border:1px dashed var(--border);"><p style="font-size:.8rem;">사용 가능한 쿠폰이 없습니다</p></div>';
    }
  } catch (e) {
    console.error("loadMyCouponsForCheckout 오류:", e);
    myCouponsList.style.display = "block";
    availableCouponsList.innerHTML =
      '<div style="padding:1.5rem;text-align:center;color:var(--light);background:var(--sage-bg);border-radius:8px;border:1px dashed var(--border);"><p style="font-size:.8rem;">쿠폰을 불러오는 중 오류가 발생했습니다</p></div>';
  }
}

async function applyMyCoupon(couponId) {
  const allCoupons = await API.getActiveCoupons();
  const coupon = allCoupons.find((c) => c.id === couponId);

  if (!coupon) {
    alert("유효하지 않은 쿠폰입니다.");
    return;
  }

  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  const result = await API.validateCoupon(coupon.code, subtotal);

  if (!result.valid) {
    alert(result.message);
    return;
  }

  appliedCoupon = result.coupon;
  const discount = API.applyCoupon(result.coupon, subtotal);

  // 쿠폰 정보 표시
  const couponInfo = document.getElementById("couponInfo");
  const couponName = document.getElementById("couponName");
  const couponCode = document.getElementById("couponCode");

  if (couponInfo && couponName) {
    couponInfo.style.display = "block";
    couponName.textContent = `${
      result.coupon.name
    } (-₩${discount.toLocaleString()})`;
  }

  if (couponCode) couponCode.value = coupon.code;

  updateCheckoutSummary();
  loadMyCouponsForCheckout(); // 목록 새로고침
}
