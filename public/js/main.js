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
// 인트로가 표시되는 동안 메인 스크롤 잠금
const introEl = document.getElementById("intro");
if (introEl && !introEl.classList.contains("hidden")) {
  document.body.style.overflow = "hidden";
}

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

setTimeout(() => {
  const intro = document.getElementById("intro");
  const main = document.getElementById("main");
  if (intro && main) {
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
}, 2500);

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
      // 최소 5개 필요하므로 반복해서 채움
      banners = [];
      while (banners.length < 5) {
        adminBanners.forEach((b) => {
          if (banners.length < 5) banners.push(b);
        });
      }
    }
  }

  // 슬라이드 카드 생성
  track.innerHTML = banners
    .map(
      (b, i) => `
    <div class="slide-card ${positions[i]}" onclick="location.href='${
        b.link || "#"
      }'">
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
  sliderInterval = setInterval(nextSlide, 3000);
}

// 슬라이더 초기화 (관리자 배너 로드)
loadBannerSlider();

// 자동 슬라이드 시작
sliderInterval = setInterval(nextSlide, 3000);

// 감정 섹션 동적 로드
function loadEmotionSection() {
  const grid = document.getElementById("emotionGrid");
  if (!grid) return;
  
  // 기본 감정 데이터
  let emotions = [
    { id: 1, key: "calm", title: "차분해지고 싶은 날", desc: "마음이 고요해지는 향" },
    { id: 2, key: "warm", title: "따뜻함이 필요한 순간", desc: "포근한 온기를 담은 향" },
    { id: 3, key: "focus", title: "집중하고 싶은 시간", desc: "맑고 깨끗한 향" },
    { id: 4, key: "refresh", title: "상쾌하고 싶을 때", desc: "활력을 주는 향" },
  ];
  
  // 관리자 감정 데이터
  if (typeof API !== "undefined" && API.getActiveEmotions) {
    const adminEmotions = API.getActiveEmotions();
    if (adminEmotions.length > 0) {
      emotions = adminEmotions;
    }
  }
  
  grid.innerHTML = emotions.map(e => `
    <div class="emotion-card ${e.key}" data-emotion="${e.key}">
      <div class="emotion-visual"></div>
      <h3 class="emotion-title">${e.title}</h3>
      <p class="emotion-desc">${e.desc}</p>
    </div>
  `).join('');
  
  // 감정 카드 클릭 이벤트
  grid.querySelectorAll('.emotion-card').forEach(card => {
    card.addEventListener('click', () => {
      const emotion = card.dataset.emotion;
      openEmotionDetail(emotion);
    });
  });
}

// 섹션 타이틀 동적 로드
function loadSectionTitles() {
  if (typeof API === "undefined" || !API.getSections) return;
  
  const sections = API.getSections();
  
  // 감정 섹션
  const emotionLabel = document.getElementById("emotionLabel");
  const emotionTitle = document.getElementById("emotionTitle");
  const emotionSubtitle = document.getElementById("emotionSubtitle");
  if (emotionLabel) emotionLabel.textContent = sections.emotionLabel || "FIND YOUR SCENT";
  if (emotionTitle) emotionTitle.textContent = sections.emotionTitle || "오늘, 어떤 기분인가요?";
  if (emotionSubtitle) emotionSubtitle.textContent = sections.emotionSubtitle || "감정에 맞는 향기를 추천해드릴게요";
  
  // 베스트 섹션
  const bestLabel = document.getElementById("bestLabel");
  const bestTitle = document.getElementById("bestTitle");
  const bestSubtitle = document.getElementById("bestSubtitle");
  const bestQuote = document.getElementById("bestQuote");
  if (bestLabel) bestLabel.textContent = sections.bestLabel || "MOST LOVED";
  if (bestTitle) bestTitle.textContent = sections.bestTitle || "다시 찾게 되는 향기";
  if (bestSubtitle) bestSubtitle.innerHTML = sections.bestSubtitle || "한 번 스친 향기가 오래 기억에 남을 때가 있어요.<br>많은 분들이 다시 찾은 향기를 소개합니다.";
  if (bestQuote) bestQuote.textContent = sections.bestQuote || "— 향기는 기억을 여는 열쇠 —";
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
    if (selectedIds.length > 0) {
      displayProducts = products.filter((p) => selectedIds.includes(p.id));
    }
  }

  grid.innerHTML = displayProducts
    .map(
      (product, index) => `
        <div class="product-card" onclick="openProductModal(${index})">
          <div class="product-image">
            ${
              product.badge
                ? `<span class="product-badge">${product.badge}</span>`
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

// 처음 로드 시 상품 렌더링 (API에서 상품 로드 후)
(async function initProducts() {
  await loadProducts();
  renderProducts();
})();

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
          <div class="test-result-icon">🌸</div>
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

function renderReviews() {
  if (!currentProduct) return;

  const reviews = getProductReviews(currentProduct.id);

  // 리뷰 개수 배지 업데이트
  const badge = document.getElementById("reviewCountBadge");
  if (badge) {
    badge.textContent = `(${reviews.length})`;
  }
}

function openReviewList() {
  const container = document.getElementById("reviewListBody");
  const subtitle = document.getElementById("reviewListSubtitle");
  if (!container) return;

  if (!currentProduct) {
    container.innerHTML = `<div class="cart-empty"><p>상품을 선택해주세요.</p></div>`;
    openModal("reviewListModal");
    return;
  }

  const reviews = getProductReviews(currentProduct.id);

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
      .map(
        (r) => `
          <div class="review-item">
            <div class="review-header">
              <span class="review-user">${r.user}</span>
              <span class="review-date">${r.date}</span>
            </div>
            <div class="review-stars">
              ${"★".repeat(r.rating)}${"☆".repeat(5 - r.rating)}
            </div>
            <p class="review-content">${r.content}</p>
          </div>
        `
      )
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
  addToCart();
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

  // ✅ 비어 있을 때: 이모지 제거 + 듀센트 감성으로 심플하게
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

// ✅ 헤더에서 쓰기 좋은 장바구니 열기 함수
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

function updateCheckoutSummary() {
  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  const shipping = subtotal >= 50000 ? 0 : 3000;
  const total = subtotal + shipping;

  const subtotalEl = document.getElementById("checkoutSubtotal");
  const shippingEl = document.getElementById("checkoutShipping");
  const totalEl = document.getElementById("checkoutTotal");

  if (!subtotalEl || !shippingEl || !totalEl) return;

  subtotalEl.textContent = "₩" + subtotal.toLocaleString();
  shippingEl.textContent =
    shipping === 0 ? "무료" : "₩" + shipping.toLocaleString();
  totalEl.textContent = "₩" + total.toLocaleString();
}

function completeOrder() {
  alert(
    "주문이 완료되었습니다!\n\n입금 계좌: 신한은행 110-123-456789\n예금주: (주)듀센트\n\n24시간 이내 입금 부탁드립니다."
  );
  cart = [];
  updateCartCount();
  renderCart();
  closeModal("checkoutModal");
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
  const userName = user.name.charAt(0) + "**";

  saveReview(currentProduct.id, {
    user: userName,
    rating,
    content,
  });

  // 입력 필드 초기화
  if (contentEl) contentEl.value = "";
  document
    .querySelectorAll('#reviewModal input[name="rating"]')
    .forEach((r) => (r.checked = false));

  alert("리뷰가 등록되었습니다. 감사합니다!");
  closeModal("reviewModal");

  // 리뷰 목록 갱신
  renderReviews();
}

// ───────────────────────────
// 9. 임시 회원/인증 로직 (백엔드 연동 전)
// ───────────────────────────
const USER_KEY = "ds_current_user";
const USERS_DB_KEY = "ds_users_db"; // 회원 목록 저장

// 회원 목록 가져오기
function getUsersDB() {
  try {
    const raw = localStorage.getItem(USERS_DB_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

// 회원 목록 저장
function setUsersDB(users) {
  localStorage.setItem(USERS_DB_KEY, JSON.stringify(users));
}

// 이메일로 회원 찾기
function findUserByEmail(email) {
  const users = getUsersDB();
  return users.find((u) => u.email.toLowerCase() === email.toLowerCase());
}

// 회원 등록
function registerUser(name, email, password) {
  const users = getUsersDB();
  const newUser = {
    id: Date.now(),
    name,
    email: email.toLowerCase(),
    password, // 실제로는 해시해야 함 (백엔드에서 처리)
    createdAt: new Date().toISOString().split("T")[0],
  };
  users.push(newUser);
  setUsersDB(users);
  return newUser;
}

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
  const removes = new Set(getOrderRemoves());
  const adds = getOrderAdds();
  const base = (baseOrders || []).filter((o) => !removes.has(o.id));
  const merged = [...adds, ...base];
  merged.sort((a, b) => {
    const ad = a.orderedAt ? new Date(a.orderedAt).getTime() : 0;
    const bd = b.orderedAt ? new Date(b.orderedAt).getTime() : 0;
    return bd - ad;
  });
  return merged;
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

  // 회원 정보 확인
  const user = findUserByEmail(email);
  if (!user) {
    alert("등록되지 않은 이메일입니다.\n회원가입을 먼저 해주세요.");
    return;
  }

  // 비밀번호 확인
  if (user.password !== password) {
    alert("비밀번호가 일치하지 않습니다.");
    return;
  }

  setCurrentUser({ email: user.email, name: user.name });
  updateAuthUI();
  closeModal("loginModal");

  // 입력 필드 초기화
  document.getElementById("loginEmail").value = "";
  document.getElementById("loginPassword").value = "";

  alert("로그인 되었습니다!");
}

function signup() {
  const name = document.getElementById("signupName")?.value.trim();
  const email = document.getElementById("signupEmail")?.value.trim();
  const password = document.getElementById("signupPassword")?.value.trim();

  if (!name || !email || !password) {
    alert("이름, 이메일, 비밀번호를 모두 입력해주세요.");
    return;
  }

  // 이름 길이 확인
  if (name.length < 2) {
    alert("이름은 2자 이상 입력해주세요.");
    return;
  }

  // 이메일 형식 확인
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    alert("올바른 이메일 형식을 입력해주세요.\n예: example@email.com");
    return;
  }

  // 비밀번호 길이 확인
  if (password.length < 4) {
    alert("비밀번호는 4자 이상 입력해주세요.");
    return;
  }

  // 이메일 중복 확인
  const existingUser = findUserByEmail(email);
  if (existingUser) {
    alert("이미 가입된 이메일입니다.\n로그인을 해주세요.");
    return;
  }

  // 회원 등록
  const newUser = registerUser(name, email, password);
  setCurrentUser({ email: newUser.email, name: newUser.name });
  updateAuthUI();
  closeModal("signupModal");

  // 입력 필드 초기화
  document.getElementById("signupName").value = "";
  document.getElementById("signupEmail").value = "";
  document.getElementById("signupPassword").value = "";

  alert("회원가입이 완료되었습니다!\n자동으로 로그인되었습니다.");
}

function logoutUser() {
  clearCurrentUser();
  updateAuthUI();
  const mypage = document.getElementById("mypageModal");
  if (mypage && mypage.classList.contains("active")) {
    closeModal("mypageModal");
  }
  alert("로그아웃 되었습니다.");
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

  // 회원 DB에서 삭제
  const users = getUsersDB();
  const filteredUsers = users.filter(
    (u) => u.email.toLowerCase() !== user.email.toLowerCase()
  );
  setUsersDB(filteredUsers);

  // 관련 데이터 삭제
  localStorage.removeItem(USER_PROFILE_OVERRIDES_KEY);
  localStorage.removeItem(PAYMENT_METHOD_KEY);
  localStorage.removeItem(WISHLIST_KEY);

  // 문의 내역에서 해당 사용자 문의 삭제
  const inquiries = JSON.parse(
    localStorage.getItem("dewscent_inquiries") || "[]"
  );
  const filteredInquiries = inquiries.filter(
    (inq) => inq.userId !== user.email
  );
  localStorage.setItem("dewscent_inquiries", JSON.stringify(filteredInquiries));

  // 로그아웃
  clearCurrentUser();
  updateAuthUI();
  closeModal("mypageModal");

  alert("회원 탈퇴가 완료되었습니다.\n이용해 주셔서 감사합니다.");
}

function renderMyPage() {
  openModal("mypageModal");
  const user = getCurrentUser();
  const body = document.getElementById("mypageBody");
  if (!body) return;
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
    API.getProfile(),
    API.getOrders({ from: mypageOrderFrom, to: mypageOrderTo }),
  ]).then(([profile, orders]) => {
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
        <div class="form-group">
          <label class="form-label">이메일</label>
          <div class="form-input" style="background:#fff">${
            mergedProfile.email || ""
          }</div>
        </div>
        <div class="form-group">
          <label class="form-label">가입일</label>
          <div class="form-input" style="background:#fff">${
            mergedProfile.joinedAt || ""
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
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
            <input type="text" id="mp_addr_label" class="form-input" placeholder="예: 기본, 회사">
            <input type="text" id="mp_addr_recipient" class="form-input" placeholder="받는 분">
            <input type="text" id="mp_addr_address" class="form-input" placeholder="주소">
            <input type="text" id="mp_addr_phone" class="form-input" placeholder="연락처">
          </div>
          <div style="display:flex;gap:.5rem;justify-content:flex-end">
            <button class="form-btn primary" style="flex:0 0 auto;min-width:140px" onclick="addAddressFromForm()">배송지 등록</button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">등록된 배송지</label>
          <div style="padding:.5rem;border:1px solid var(--border);border-radius:12px;background:#fff">
            ${
              addresses.length
                ? addresses
                    .map(
                      (a) => `
              <div style="display:flex;justify-content:space-between;gap:1rem;margin-bottom:.5rem">
                <div>
                  <strong>${a.label}</strong> · ${a.recipient}<br/>
                  ${a.address} · ${a.phone}
                </div>
                <span>
                  <button class="form-btn secondary" style="padding:.4rem .8rem;margin:0" onclick="deleteAddress(${a.id})">삭제</button>
                </span>
              </div>`
                    )
                    .join("")
                : '<span style="color:var(--light)">등록된 배송지가 없습니다.</span>'
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

    if (mypageCurrentTab === "orders") {
      const fromVal = mypageOrderFrom || "";
      const toVal = mypageOrderTo || "";
      const filters = `
        <div class="orders-filters">
          <div class="orders-filters-left">
            <input type="date" id="mp_filter_from" class="form-input" value="${fromVal}">
            <span style="align-self:center;color:var(--light)">~</span>
            <input type="date" id="mp_filter_to" class="form-input" value="${toVal}">
            <button class="form-btn ivory btn-compact" onclick="applyOrderFilter()">조회</button>
          </div>
          <div class="orders-filters-right">
            <button class="form-btn secondary btn-compact" onclick="setQuickOrderFilter('all')">전체</button>
          </div>
        </div>
      `;
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
              <tr>
                <td style="padding:.6rem .8rem;border-top:1px solid var(--border)">${
                  o.id
                }</td>
                <td style="padding:.6rem .8rem;border-top:1px solid var(--border)">₩${(
                  o.total || 0
                ).toLocaleString()}</td>
                <td style="padding:.6rem .8rem;border-top:1px solid var(--border)">${
                  o.status || ""
                }</td>
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
        `<div style="padding:1rem;border:1px solid var(--border);border-radius:12px;background:#fff;color:var(--light);text-align:center">주문 내역이 없습니다.</div>`;
      content = filters + content;
    }

    body.innerHTML = `${tabs}${content}<button class="form-btn ivory" onclick="closeModal('mypageModal')">닫기</button>`;
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

function submitInquiry() {
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

  const inquiry = {
    id: Date.now(),
    userId: user.email,
    type: type,
    orderNo: orderNo || null,
    title: title,
    content: content,
    status: "waiting", // waiting, answered
    answer: null,
    createdAt: new Date().toISOString().split("T")[0],
    answeredAt: null,
  };

  const list = getInquiries();
  list.unshift(inquiry);
  setInquiries(list);

  // 폼 초기화
  document.getElementById("inquiryType").value = "";
  document.getElementById("inquiryOrderNo").value = "";
  document.getElementById("inquiryTitle").value = "";
  document.getElementById("inquiryContent").value = "";

  alert("문의가 등록되었습니다. 영업일 기준 1~2일 내 답변드릴게요!");
  closeModal("inquiryModal");
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

function renderInquiryList() {
  const container = document.getElementById("inquiryListBody");
  if (!container) return;

  const user = getCurrentUser();
  if (!user) {
    container.innerHTML = `<div class="inquiry-empty"><p>로그인이 필요합니다.</p></div>`;
    return;
  }

  const allInquiries = getInquiries();
  const myInquiries = allInquiries.filter((inq) => inq.userId === user.email);

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

  const popups = API.getActivePopups();
  const hiddenPopups = getHiddenPopups();

  // 숨긴 팝업 제외
  const visiblePopups = popups.filter((p) => !hiddenPopups[p.id]).slice(0, 5); // 최대 5개

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
    hidePopupForWeek(id);
    currentPopupIndex++;
    renderCurrentPopup();
  };

  renderCurrentPopup();
}

// 페이지 로드 시 팝업 표시 (메인 페이지에서만)
if (document.querySelector(".slider-section")) {
  setTimeout(showSitePopups, 1000); // 1초 후 팝업 표시
}
