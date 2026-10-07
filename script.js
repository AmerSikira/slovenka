"use strict";

// Catalogue data is independent of page markup. Prices and sizes are examples.
const PRODUCTS = [
  {
    id: "aria",
    name: "ARIA",
    label: {
      bs: "Hlače širokih nogavica",
      en: "Wide-leg trousers",
    },
    type: "trousers",
    price: 129,
    description: {
      bs: "Čista linija, visoki struk i široke nogavice. ARIA daje lakoću poslovnim kombinacijama i jednostavnim svakodnevnim izdanjima.",
      en: "Clean lines, a high waist and wide legs. ARIA brings ease to tailored looks and everyday dressing.",
    },
    colors: [
      {
        id: "black",
        name: {
          bs: "Crna",
          en: "Black",
        },
        hex: "#262622",
        cover: "ARIA/Sređeno/ARIA 2.png",
        images: [2, 7, 1, 6],
      },
      {
        id: "navy",
        name: {
          bs: "Tamnoplava",
          en: "Navy",
        },
        hex: "#303744",
        cover: "ARIA/Sređeno/ARIA 3.png",
        images: [3, 8, 9, 10],
      },
    ],
    sizes: ["36", "38", "40", "42", "44"],
    unavailable: ["44"],
  },
  {
    id: "coco",
    name: "COCO",
    label: {
      bs: "Sako s dvorednim kopčanjem",
      en: "Double-breasted blazer",
    },
    type: "blazers",
    price: 189,
    description: {
      bs: "Precizan kroj i duboka zelena nijansa. Sako COCO ima dvoredno kopčanje, naglašen rever i džepove s preklopom.",
      en: "A considered cut in deep green. The COCO blazer features double-breasted buttons, a defined lapel and flap pockets.",
    },
    colors: [
      {
        id: "green",
        name: {
          bs: "Zelena",
          en: "Green",
        },
        hex: "#1e5940",
        cover: "COCO/Sređene/COCO 1.png",
        images: [1, 2, 3, 4],
      },
    ],
    sizes: ["36", "38", "40", "42", "44"],
    unavailable: [],
  },
  {
    id: "alba",
    name: "ALBA",
    label: {
      bs: "Hlače ravnog kroja",
      en: "Straight-leg trousers",
    },
    type: "trousers",
    price: 109,
    description: {
      bs: "Ravne nogavice i uredan pojas stvaraju jednostavnu siluetu. ALBA se lako kombinuje sa sakoom, košuljom ili laganim pletivom.",
      en: "Straight legs and a neat waistband create an effortless silhouette. Pair ALBA with a blazer, shirt or light knit.",
    },
    colors: [
      {
        id: "brown",
        name: {
          bs: "Tamnosmeđa",
          en: "Dark brown",
        },
        hex: "#4b4338",
        cover: "ALBA/Sređene/ALBA 2.png",
        images: [2, 3, 4, 5],
      },
      {
        id: "black",
        name: {
          bs: "Crna",
          en: "Black",
        },
        hex: "#262622",
        cover: "ALBA/Sređene/ALBA 1.png",
        images: [1],
      },
      {
        id: "navy",
        name: {
          bs: "Tamnoplava",
          en: "Navy",
        },
        hex: "#303744",
        cover: "ALBA/Sređene/ALBA 7.png",
        images: [7, 6],
      },
    ],
    sizes: ["36", "38", "40", "42", "44"],
    unavailable: ["36"],
  },
  {
    id: "signature",
    name: "SIGNATURE",
    label: {
      bs: "Odijelo s dvorednim sakoom",
      en: "Double-breasted tailored suit",
    },
    type: "suits",
    price: 299,
    description: {
      bs: "Sako naglašenog struka i široke hlače u jednoj skladnoj cjelini. SIGNATURE je odijelo za radni dan, susrete i posebne prilike.",
      en: "A defined-waist blazer and wide-leg trousers in one balanced silhouette. SIGNATURE is a suit for workdays, meetings and special occasions.",
    },
    colors: [
      {
        id: "olive",
        name: {
          bs: "Tamna maslina",
          en: "Dark olive",
        },
        hex: "#37392e",
        cover: "SIGNATURE/Sređene/SIGNATURE 1.png",
        images: [1, 2, 3],
      },
    ],
    sizes: ["36", "38", "40", "42", "44"],
    unavailable: [],
  },
  {
    id: "hasi",
    name: "HASI",
    label: {
      bs: "Hlače suženih nogavica",
      en: "Tapered trousers",
    },
    type: "trousers",
    price: 99,
    description: {
      bs: "Jednostavne crne hlače sa suženom nogavicom i dužinom iznad gležnja. HASI ostavlja prostor za vaš stil, od jutra do večeri.",
      en: "Simple black trousers with a tapered leg and ankle-length cut. HASI makes room for your style, morning to evening.",
    },
    colors: [
      {
        id: "black",
        name: {
          bs: "Crna",
          en: "Black",
        },
        hex: "#262622",
        cover: "HASI/Sređene/HASI 3.png",
        images: [3, 1, 2],
      },
    ],
    sizes: ["36", "38", "40", "42", "44"],
    unavailable: ["44"],
  },
];
// Sample body circumferences in cm, shared by the preview catalogue.
// Replace with approved sizing data before live sales; these are not garment dimensions.
const BODY_MEASUREMENTS = {
  "36": { bust: 84, waist: 66, hips: 92 },
  "38": { bust: 88, waist: 70, hips: 96 },
  "40": { bust: 92, waist: 74, hips: 100 },
  "42": { bust: 96, waist: 78, hips: 104 },
  "44": { bust: 100, waist: 82, hips: 108 },
};
const CATEGORIES = [
  {
    id: "trousers",
    bs: "Hlače",
    en: "Trousers",
    image: "ARIA/Sređeno/ARIA 2.png",
    count: "03",
  },
  {
    id: "blazers",
    bs: "Sakoi",
    en: "Blazers",
    image: "COCO/Sređene/COCO 1.png",
    count: "01",
  },
  {
    id: "suits",
    bs: "Odijela",
    en: "Suits",
    image: "SIGNATURE/Sređene/SIGNATURE 1.png",
    count: "01",
  },
  {
    id: "all",
    bs: "Poslovni stil",
    en: "The tailoring edit",
    image: "HASI/Primjena/HASI 1.png",
    count: "05",
  },
];
const IMAGES = {
  "ALBA/Sređene/ALBA 1.png": {
    src: "assets/images/ALBA-Sredene-ALBA-1.webp",
    fallback: "assets/images/ALBA-Sredene-ALBA-1.jpg",
    srcSet:
      "assets/images/ALBA-Sredene-ALBA-1-480.webp 480w, assets/images/ALBA-Sredene-ALBA-1-960.webp 960w, assets/images/ALBA-Sredene-ALBA-1.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "ALBA/Sređene/ALBA 2.png": {
    src: "assets/images/ALBA-Sredene-ALBA-2.webp",
    fallback: "assets/images/ALBA-Sredene-ALBA-2.jpg",
    srcSet:
      "assets/images/ALBA-Sredene-ALBA-2-480.webp 480w, assets/images/ALBA-Sredene-ALBA-2-960.webp 960w, assets/images/ALBA-Sredene-ALBA-2.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "ALBA/Sređene/ALBA 3.png": {
    src: "assets/images/ALBA-Sredene-ALBA-3.webp",
    fallback: "assets/images/ALBA-Sredene-ALBA-3.jpg",
    srcSet:
      "assets/images/ALBA-Sredene-ALBA-3-480.webp 480w, assets/images/ALBA-Sredene-ALBA-3-960.webp 960w, assets/images/ALBA-Sredene-ALBA-3.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "ALBA/Sređene/ALBA 4.png": {
    src: "assets/images/ALBA-Sredene-ALBA-4.webp",
    fallback: "assets/images/ALBA-Sredene-ALBA-4.jpg",
    srcSet:
      "assets/images/ALBA-Sredene-ALBA-4-480.webp 480w, assets/images/ALBA-Sredene-ALBA-4-960.webp 960w, assets/images/ALBA-Sredene-ALBA-4.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "ALBA/Sređene/ALBA 5.png": {
    src: "assets/images/ALBA-Sredene-ALBA-5.webp",
    fallback: "assets/images/ALBA-Sredene-ALBA-5.jpg",
    srcSet:
      "assets/images/ALBA-Sredene-ALBA-5-480.webp 480w, assets/images/ALBA-Sredene-ALBA-5-960.webp 960w, assets/images/ALBA-Sredene-ALBA-5.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "ALBA/Sređene/ALBA 6.png": {
    src: "assets/images/ALBA-Sredene-ALBA-6.webp",
    fallback: "assets/images/ALBA-Sredene-ALBA-6.jpg",
    srcSet:
      "assets/images/ALBA-Sredene-ALBA-6-480.webp 480w, assets/images/ALBA-Sredene-ALBA-6-960.webp 960w, assets/images/ALBA-Sredene-ALBA-6.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "ALBA/Sređene/ALBA 7.png": {
    src: "assets/images/ALBA-Sredene-ALBA-7.webp",
    fallback: "assets/images/ALBA-Sredene-ALBA-7.jpg",
    srcSet:
      "assets/images/ALBA-Sredene-ALBA-7-480.webp 480w, assets/images/ALBA-Sredene-ALBA-7-960.webp 960w, assets/images/ALBA-Sredene-ALBA-7.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "ARIA/Sređeno/ARIA 1.png": {
    src: "assets/images/ARIA-Sredeno-ARIA-1.webp",
    fallback: "assets/images/ARIA-Sredeno-ARIA-1.jpg",
    srcSet:
      "assets/images/ARIA-Sredeno-ARIA-1-480.webp 480w, assets/images/ARIA-Sredeno-ARIA-1-960.webp 960w, assets/images/ARIA-Sredeno-ARIA-1.webp 1023w",
    width: 1023,
    height: 1537,
  },
  "ARIA/Sređeno/ARIA 10.png": {
    src: "assets/images/ARIA-Sredeno-ARIA-10.webp",
    fallback: "assets/images/ARIA-Sredeno-ARIA-10.jpg",
    srcSet:
      "assets/images/ARIA-Sredeno-ARIA-10-480.webp 480w, assets/images/ARIA-Sredeno-ARIA-10-960.webp 960w, assets/images/ARIA-Sredeno-ARIA-10.webp 1023w",
    width: 1023,
    height: 1537,
  },
  "ARIA/Sređeno/ARIA 2.png": {
    src: "assets/images/ARIA-Sredeno-ARIA-2.webp",
    fallback: "assets/images/ARIA-Sredeno-ARIA-2.jpg",
    srcSet:
      "assets/images/ARIA-Sredeno-ARIA-2-480.webp 480w, assets/images/ARIA-Sredeno-ARIA-2-960.webp 960w, assets/images/ARIA-Sredeno-ARIA-2.webp 1023w",
    width: 1023,
    height: 1538,
  },
  "ARIA/Sređeno/ARIA 3.png": {
    src: "assets/images/ARIA-Sredeno-ARIA-3.webp",
    fallback: "assets/images/ARIA-Sredeno-ARIA-3.jpg",
    srcSet:
      "assets/images/ARIA-Sredeno-ARIA-3-480.webp 480w, assets/images/ARIA-Sredeno-ARIA-3-960.webp 960w, assets/images/ARIA-Sredeno-ARIA-3.webp 1023w",
    width: 1023,
    height: 1538,
  },
  "ARIA/Sređeno/ARIA 6.png": {
    src: "assets/images/ARIA-Sredeno-ARIA-6.webp",
    fallback: "assets/images/ARIA-Sredeno-ARIA-6.jpg",
    srcSet:
      "assets/images/ARIA-Sredeno-ARIA-6-480.webp 480w, assets/images/ARIA-Sredeno-ARIA-6-960.webp 960w, assets/images/ARIA-Sredeno-ARIA-6.webp 1023w",
    width: 1023,
    height: 1537,
  },
  "ARIA/Sređeno/ARIA 7.png": {
    src: "assets/images/ARIA-Sredeno-ARIA-7.webp",
    fallback: "assets/images/ARIA-Sredeno-ARIA-7.jpg",
    srcSet:
      "assets/images/ARIA-Sredeno-ARIA-7-480.webp 480w, assets/images/ARIA-Sredeno-ARIA-7-960.webp 960w, assets/images/ARIA-Sredeno-ARIA-7.webp 1023w",
    width: 1023,
    height: 1537,
  },
  "ARIA/Sređeno/ARIA 8.png": {
    src: "assets/images/ARIA-Sredeno-ARIA-8.webp",
    fallback: "assets/images/ARIA-Sredeno-ARIA-8.jpg",
    srcSet:
      "assets/images/ARIA-Sredeno-ARIA-8-480.webp 480w, assets/images/ARIA-Sredeno-ARIA-8-960.webp 960w, assets/images/ARIA-Sredeno-ARIA-8.webp 1023w",
    width: 1023,
    height: 1537,
  },
  "ARIA/Sređeno/ARIA 9.png": {
    src: "assets/images/ARIA-Sredeno-ARIA-9.webp",
    fallback: "assets/images/ARIA-Sredeno-ARIA-9.jpg",
    srcSet:
      "assets/images/ARIA-Sredeno-ARIA-9-480.webp 480w, assets/images/ARIA-Sredeno-ARIA-9-960.webp 960w, assets/images/ARIA-Sredeno-ARIA-9.webp 1023w",
    width: 1023,
    height: 1538,
  },
  "COCO/Sređene/COCO 1.png": {
    src: "assets/images/COCO-Sredene-COCO-1.webp",
    fallback: "assets/images/COCO-Sredene-COCO-1.jpg",
    srcSet:
      "assets/images/COCO-Sredene-COCO-1-480.webp 480w, assets/images/COCO-Sredene-COCO-1-960.webp 960w, assets/images/COCO-Sredene-COCO-1.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "COCO/Sređene/COCO 2.png": {
    src: "assets/images/COCO-Sredene-COCO-2.webp",
    fallback: "assets/images/COCO-Sredene-COCO-2.jpg",
    srcSet:
      "assets/images/COCO-Sredene-COCO-2-480.webp 480w, assets/images/COCO-Sredene-COCO-2-960.webp 960w, assets/images/COCO-Sredene-COCO-2.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "COCO/Sređene/COCO 3.png": {
    src: "assets/images/COCO-Sredene-COCO-3.webp",
    fallback: "assets/images/COCO-Sredene-COCO-3.jpg",
    srcSet:
      "assets/images/COCO-Sredene-COCO-3-480.webp 480w, assets/images/COCO-Sredene-COCO-3-960.webp 960w, assets/images/COCO-Sredene-COCO-3.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "COCO/Sređene/COCO 4.png": {
    src: "assets/images/COCO-Sredene-COCO-4.webp",
    fallback: "assets/images/COCO-Sredene-COCO-4.jpg",
    srcSet:
      "assets/images/COCO-Sredene-COCO-4-480.webp 480w, assets/images/COCO-Sredene-COCO-4-960.webp 960w, assets/images/COCO-Sredene-COCO-4.webp 1023w",
    width: 1023,
    height: 1537,
  },
  "HASI/Primjena/HASI 1.png": {
    src: "assets/images/HASI-Primjena-HASI-1.webp",
    fallback: "assets/images/HASI-Primjena-HASI-1.jpg",
    srcSet:
      "assets/images/HASI-Primjena-HASI-1-480.webp 480w, assets/images/HASI-Primjena-HASI-1-960.webp 960w, assets/images/HASI-Primjena-HASI-1.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "HASI/Primjena/HASI 4.png": {
    src: "assets/images/HASI-Primjena-HASI-4.webp",
    fallback: "assets/images/HASI-Primjena-HASI-4.jpg",
    srcSet:
      "assets/images/HASI-Primjena-HASI-4-480.webp 480w, assets/images/HASI-Primjena-HASI-4-960.webp 960w, assets/images/HASI-Primjena-HASI-4.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "HASI/Sređene/HASI 1.png": {
    src: "assets/images/HASI-Sredene-HASI-1.webp",
    fallback: "assets/images/HASI-Sredene-HASI-1.jpg",
    srcSet:
      "assets/images/HASI-Sredene-HASI-1-480.webp 480w, assets/images/HASI-Sredene-HASI-1-960.webp 960w, assets/images/HASI-Sredene-HASI-1.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "HASI/Sređene/HASI 2.png": {
    src: "assets/images/HASI-Sredene-HASI-2.webp",
    fallback: "assets/images/HASI-Sredene-HASI-2.jpg",
    srcSet:
      "assets/images/HASI-Sredene-HASI-2-480.webp 480w, assets/images/HASI-Sredene-HASI-2-960.webp 960w, assets/images/HASI-Sredene-HASI-2.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "HASI/Sređene/HASI 3.png": {
    src: "assets/images/HASI-Sredene-HASI-3.webp",
    fallback: "assets/images/HASI-Sredene-HASI-3.jpg",
    srcSet:
      "assets/images/HASI-Sredene-HASI-3-480.webp 480w, assets/images/HASI-Sredene-HASI-3-960.webp 960w, assets/images/HASI-Sredene-HASI-3.webp 1243w",
    width: 1243,
    height: 1265,
  },
  "IMG_2501.jpeg": {
    src: "assets/images/IMG_2501.webp",
    fallback: "assets/images/IMG_2501.jpg",
    srcSet:
      "assets/images/IMG_2501-480.webp 480w, assets/images/IMG_2501-960.webp 960w, assets/images/IMG_2501.webp 1062w",
    width: 1062,
    height: 1596,
  },
  "IMG_2744.jpeg": {
    src: "assets/images/IMG_2744.webp",
    fallback: "assets/images/IMG_2744.jpg",
    srcSet:
      "assets/images/IMG_2744-480.webp 480w, assets/images/IMG_2744-960.webp 960w, assets/images/IMG_2744.webp 1008w",
    width: 1008,
    height: 1515,
  },
  "IMG_2760.jpeg": {
    src: "assets/images/IMG_2760.webp",
    fallback: "assets/images/IMG_2760.jpg",
    srcSet:
      "assets/images/IMG_2760-480.webp 480w, assets/images/IMG_2760-960.webp 960w, assets/images/IMG_2760.webp 1600w",
    width: 1600,
    height: 1064,
  },
  "IMG_2778.jpeg": {
    src: "assets/images/IMG_2778.webp",
    fallback: "assets/images/IMG_2778.jpg",
    srcSet:
      "assets/images/IMG_2778-480.webp 480w, assets/images/IMG_2778-960.webp 960w, assets/images/IMG_2778.webp 1600w",
    width: 1600,
    height: 1064,
  },
  "IMG_2781.jpeg": {
    src: "assets/images/IMG_2781.webp",
    fallback: "assets/images/IMG_2781.jpg",
    srcSet:
      "assets/images/IMG_2781-480.webp 480w, assets/images/IMG_2781-960.webp 960w, assets/images/IMG_2781.webp 1600w",
    width: 1600,
    height: 1064,
  },
  "IMG_2795.jpeg": {
    src: "assets/images/IMG_2795.webp",
    fallback: "assets/images/IMG_2795.jpg",
    srcSet:
      "assets/images/IMG_2795-480.webp 480w, assets/images/IMG_2795-960.webp 960w, assets/images/IMG_2795.webp 1600w",
    width: 1600,
    height: 1064,
  },
  "IMG_2804.jpeg": {
    src: "assets/images/IMG_2804.webp",
    fallback: "assets/images/IMG_2804.jpg",
    srcSet:
      "assets/images/IMG_2804-480.webp 480w, assets/images/IMG_2804-960.webp 960w, assets/images/IMG_2804.webp 1600w",
    width: 1600,
    height: 1064,
  },
  "IMG_2822.jpeg": {
    src: "assets/images/IMG_2822.webp",
    fallback: "assets/images/IMG_2822.jpg",
    srcSet:
      "assets/images/IMG_2822-480.webp 480w, assets/images/IMG_2822-960.webp 960w, assets/images/IMG_2822.webp 1600w",
    width: 1600,
    height: 1064,
  },
  "IMG_2827.jpeg": {
    src: "assets/images/IMG_2827.webp",
    fallback: "assets/images/IMG_2827.jpg",
    srcSet:
      "assets/images/IMG_2827-480.webp 480w, assets/images/IMG_2827-960.webp 960w, assets/images/IMG_2827.webp 1600w",
    width: 1600,
    height: 1064,
  },
  "IMG_2829.jpeg": {
    src: "assets/images/IMG_2829.webp",
    fallback: "assets/images/IMG_2829.jpg",
    srcSet:
      "assets/images/IMG_2829-480.webp 480w, assets/images/IMG_2829-960.webp 960w, assets/images/IMG_2829.webp 1600w",
    width: 1600,
    height: 1064,
  },
  "IMG_2838.jpeg": {
    src: "assets/images/IMG_2838.webp",
    fallback: "assets/images/IMG_2838.jpg",
    srcSet:
      "assets/images/IMG_2838-480.webp 480w, assets/images/IMG_2838-960.webp 960w, assets/images/IMG_2838.webp 1600w",
    width: 1600,
    height: 1064,
  },
  "IMG_2842.jpeg": {
    src: "assets/images/IMG_2842.webp",
    fallback: "assets/images/IMG_2842.jpg",
    srcSet:
      "assets/images/IMG_2842-480.webp 480w, assets/images/IMG_2842-960.webp 960w, assets/images/IMG_2842.webp 1600w",
    width: 1600,
    height: 1064,
  },
  "IMG_2843.jpeg": {
    src: "assets/images/IMG_2843.webp",
    fallback: "assets/images/IMG_2843.jpg",
    srcSet:
      "assets/images/IMG_2843-480.webp 480w, assets/images/IMG_2843-960.webp 960w, assets/images/IMG_2843.webp 1600w",
    width: 1600,
    height: 1064,
  },
  "SIGNATURE/Sređene/SIGNATURE 1.png": {
    src: "assets/images/SIGNATURE-Sredene-SIGNATURE-1.webp",
    fallback: "assets/images/SIGNATURE-Sredene-SIGNATURE-1.jpg",
    srcSet:
      "assets/images/SIGNATURE-Sredene-SIGNATURE-1-480.webp 480w, assets/images/SIGNATURE-Sredene-SIGNATURE-1-960.webp 960w, assets/images/SIGNATURE-Sredene-SIGNATURE-1.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "SIGNATURE/Sređene/SIGNATURE 2.png": {
    src: "assets/images/SIGNATURE-Sredene-SIGNATURE-2.webp",
    fallback: "assets/images/SIGNATURE-Sredene-SIGNATURE-2.jpg",
    srcSet:
      "assets/images/SIGNATURE-Sredene-SIGNATURE-2-480.webp 480w, assets/images/SIGNATURE-Sredene-SIGNATURE-2-960.webp 960w, assets/images/SIGNATURE-Sredene-SIGNATURE-2.webp 1254w",
    width: 1254,
    height: 1254,
  },
  "SIGNATURE/Sređene/SIGNATURE 3.png": {
    src: "assets/images/SIGNATURE-Sredene-SIGNATURE-3.webp",
    fallback: "assets/images/SIGNATURE-Sredene-SIGNATURE-3.jpg",
    srcSet:
      "assets/images/SIGNATURE-Sredene-SIGNATURE-3-480.webp 480w, assets/images/SIGNATURE-Sredene-SIGNATURE-3-960.webp 960w, assets/images/SIGNATURE-Sredene-SIGNATURE-3.webp 1254w",
    width: 1254,
    height: 1254,
  },
};

// Small helpers. Everything below uses native browser APIs.
const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) =>
  Array.from(root.querySelectorAll(selector));
const escapeHTML = (value) =>
  String(value ?? "").replace(
    /[&<>"']/g,
    (char) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[
        char
      ],
  );
const read = (key, fallback) => {
  try {
    return JSON.parse(localStorage.getItem(key)) ?? fallback;
  } catch {
    return fallback;
  }
};
const write = (key, value) => localStorage.setItem(key, JSON.stringify(value));
// Each physical page declares its language; preferences never replace page content.
const lang = document.documentElement.lang === "en" ? "en" : "bs";
const pageUrl = (path, language = lang) => {
  const url = new URL(path, "https://local.invalid");
  const name = url.pathname === "/" ? "index" : url.pathname.slice(1).replaceAll("/", "-");
  return `${name}${language === "en" ? "-en" : ""}.html${url.search}${url.hash}`;
};
const t = (bs, en) => (lang === "bs" ? bs : en);
const money = (amount) =>
  new Intl.NumberFormat(lang === "bs" ? "bs-BA" : "en-GB", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount) + " KM";
const normalise = (value) =>
  value
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d");
const icon = (name, size = 18) => {
  const paths = {
    arrow: '<path d="M7 17 17 7M7 7h10v10"/>',
    right: '<path d="M5 12h14m-6-6 6 6-6 6"/>',
    close: '<path d="m6 6 12 12M6 18 18 6"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    minus: '<path d="M5 12h14"/>',
    check: '<path d="m5 12 4 4L19 6"/>',
    search: '<circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/>',
    bag: '<path d="M6 7h12l2 14H4L6 7Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
  };
  return `<svg aria-hidden="true" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">${paths[name] || paths.arrow}</svg>`;
};
function image(path, alt = "", className = "", eager = false) {
  const m = IMAGES[path];
  if (!m) return "";
  return `<picture class="responsive-image"><source type="image/webp" srcset="${escapeHTML(m.srcSet)}" sizes="${eager ? "" : "auto, "}(max-width:767px) 100vw, 50vw"><img src="${m.fallback}" width="${m.width}" height="${m.height}" alt="${escapeHTML(alt)}" class="${className}" loading="${eager ? "eager" : "lazy"}" ${eager ? 'fetchpriority="high"' : ""}></picture>`;
}
function template(name) {
  return document.getElementById(`${name}-${lang}`)?.innerHTML || "";
}
let bag = read("slovenka-bag", []).filter(
  (row) =>
    PRODUCTS.some(
      (p) =>
        p.id === row.id &&
        p.sizes.includes(row.size) &&
        !p.unavailable.includes(row.size) &&
        p.colors.some((c) => c.id === row.color),
    ) &&
    Number.isInteger(row.qty) &&
    row.qty > 0 &&
    row.qty <= 5,
);
let productState = null;
const dialog = $("#site-dialog");
let dialogKind = "";
let dialogTrigger = null;
let checkoutRequestId = null;
function route() {
  return { path: document.body.dataset.page || "/", params: new URLSearchParams(location.search) };
}
function navigate(path) { location.href = pageUrl(path); }
function updateBag() {
  write("slovenka-bag", bag);
  const count = bag.reduce((sum, x) => sum + x.qty, 0);
  $$(".bag-count").forEach((node) => node.textContent = count);
  $(".bag-trigger")?.setAttribute(
    "aria-label",
    `${t("Korpa", "Bag")} (${count})`,
  );
  document.dispatchEvent(new CustomEvent("slovenka:bag-updated"));
}
function total() {
  return bag.reduce(
    (sum, x) => sum + PRODUCTS.find((p) => p.id === x.id).price * x.qty,
    0,
  );
}
function colorButtons(p, selected, context = "card") {
  return p.colors
    .map(
      (c) =>
        `<button type="button" class="swatch ${c.id === selected ? "selected" : ""}" style="--swatch:${c.hex}" data-action="${context}-color" data-product="${p.id}" data-color="${c.id}" aria-label="${c.name[lang]}" aria-pressed="${c.id === selected}"></button>`,
    )
    .join("");
}
function productCard(p, colorId = p.colors[0].id) {
  const c = p.colors.find((c) => c.id === colorId) || p.colors[0];
  const href = pageUrl(`/product/${p.id}?color=${c.id}`);
  const second = c.images[1];
  return `<article class="product-card" data-product="${p.id}"><a class="product-image" href="${href}">${image(c.cover, `${p.name} — ${p.label[lang]} — ${c.name[lang]}`)}${second ? image(galleryPath(p, second), "", "hover-image") : ""}<span class="product-tag">${p.name}</span></a><div class="product-card-top"><a href="${href}"><h3>${p.name}</h3></a><span>${money(p.price)}</span></div><a class="product-description" href="${href}">${p.label[lang]}</a><div class="swatches">${colorButtons(p, c.id)}<span>${t("Primjer cijene", "Sample price")}</span></div></article>`;
}
function galleryPath(p, n) {
  return `${p.name}/${p.id === "aria" ? "Sređeno" : "Sređene"}/${p.name} ${n}.png`;
}
// Catalogue controls preserve filter and sort state in the URL.
function matchingProducts(params = route().params) {
  const category = params.get("category") || "all",
    q = params.get("q") || "",
    color = params.get("color"),
    size = params.get("size"),
    price = params.get("price");
  const list = PRODUCTS.filter(
    (p) =>
      (category === "all" || p.type === category) &&
      (!q ||
        normalise(`${p.name} ${p.label.bs} ${p.label.en}`).includes(
          normalise(q),
        )) &&
      (!color || p.colors.some((c) => c.id === color)) &&
      (!size || (p.sizes.includes(size) && !p.unavailable.includes(size))) &&
      (!price || p.price <= Number(price)),
  );
  if (params.get("sort") === "price-asc")
    list.sort((a, b) => a.price - b.price);
  if (params.get("sort") === "price-desc")
    list.sort((a, b) => b.price - a.price);
  return list;
}
function setupShop() {
  $$(".desktop-filters select").forEach(
    (s, i) => (s.dataset.filter = ["color", "size", "price"][i]),
  );
  $(".sort-label select").dataset.filter = "sort";
  $(".filter-trigger").dataset.action = "filters";
  renderShop();
}
function renderShop() {
  const { params } = route(),
    category = params.get("category") || "all",
    q = params.get("q") || "";
  const list = matchingProducts(params);
  $("#main h1").textContent = q
    ? `${t("Rezultati za", "Results for")} “${q}”`
    : category === "all"
      ? t("Odjeća za vaš ritam.", "Clothes for your rhythm.")
      : CATEGORIES.find((c) => c.id === category)?.[lang] ||
        t("Sva odjeća", "Shop all");
  $$(".category-tabs a").forEach((a) => {
    const id =
      new URL(
        a.getAttribute("href"),
        "https://local.invalid",
      ).searchParams.get("category") || "all";
    a.classList.toggle("active", id === category);
  });
  $(".listing-toolbar>span").textContent =
    `${list.length} ${t("modela", "pieces")}`;
  $$("[data-filter]").forEach(
    (s) =>
      (s.value =
        params.get(s.dataset.filter) ||
        (s.dataset.filter === "sort" ? "featured" : "")),
  );
  let area = $(".listing-results");
  if (!area) {
    area = document.createElement("div");
    area.className = "listing-results";
    $(".listing-grid")?.replaceWith(area);
  }
  area.innerHTML = list.length
    ? `<div class="product-grid listing-grid">${list.map((p) => productCard(p)).join("")}</div>`
    : `<div class="empty-state">${icon("search", 32)}<h2>${t("Nema modela za ovaj izbor", "No pieces match this selection")}</h2><p>${t("Pokušajte drugu boju ili veličinu, ili pogledajte cijelu kolekciju.", "Try a different colour or size, or explore the whole collection.")}</p><a class="button" href="${pageUrl(`/shop`)}">${t("Sva odjeća", "Shop all")}</a></div>`;
  $(".active-filters")?.remove();
  const active = ["color", "size", "price", "q"]
    .map((key) => params.get(key))
    .filter(Boolean);
  if (active.length) {
    const node = document.createElement("div");
    node.className = "active-filters";
    node.innerHTML =
      active.map((v) => `<span>${escapeHTML(v)}</span>`).join("") +
      `<button type="button" class="underlined" data-action="clear-filters">${t("Ukloni filtere", "Clear filters")}</button>`;
    $(".listing-toolbar").after(node);
  }
  const apply = $('#dialog-content [data-action="apply-filters"]');
  if (apply)
    apply.textContent = `${t("Prikaži", "Show")} ${list.length} ${t("modela", "pieces")}`;
}
function changeFilter(key, value) {
  const { params } = route();
  value ? params.set(key, value) : params.delete(key);
  location.href = pageUrl(`/shop${params.size ? "?" + params : ""}`);
}

// Product galleries and explicit variant selection.
function setupProduct(id, colorId) {
  const p = PRODUCTS.find((p) => p.id === id);
  if (!p) {
    $("#main").innerHTML =
      `<section class="container empty-page"><h1>${t("Model nije pronađen", "Piece not found")}</h1><a class="button" href="${pageUrl(`/shop`)}">${t("Sva odjeća", "Shop all")}</a></section>`;
    return;
  }
  productState = {
    p,
    color: p.colors.find((c) => c.id === colorId) || p.colors[0],
    size: "",
    imageIndex: 0,
  };

  updateGallery();
}

function measurementChart() {
  return `<section class="measurement-chart" aria-label="Tjelesne mjere" lang="bs">
    <div class="measurement-table">${measurementTable()}</div>
    <p class="measurement-note">Primjeri tjelesnih mjera. Potvrđene mjere za ovaj model još nisu dostupne.</p>
  </section>`;
}

function measurementTable() {
  const { p, size } = productState;
  const columns = p.type === "trousers" ? ["waist", "hips"] : ["bust", "waist", "hips"];
  const labels = {
    bust: "Grudi",
    waist: "Struk",
    hips: "Bokovi",
  };
  return `<table>
    <caption>Obimi tijela (cm)</caption>
    <thead><tr><th scope="col">Veličina</th>${columns.map((key) => `<th scope="col">${labels[key]}</th>`).join("")}</tr></thead>
    <tbody>${p.sizes.map((s) => `<tr class="${s === size ? "selected" : ""}"><th scope="row">${s}${s === size ? '<span class="sr-only"> — odabrana veličina</span>' : ""}</th>${columns.map((key) => `<td>${BODY_MEASUREMENTS[s][key]}</td>`).join("")}</tr>`).join("")}</tbody>
  </table>`;
}

function updateGallery() {
  const { p, color, imageIndex } = productState;
  const paths = color.images.map((n) => galleryPath(p, n));
  if (p.id === "hasi") paths.push("HASI/Primjena/HASI 1.png");
  $(".variant-group p").innerHTML =
    `${t("Boja", "Colour")}: <strong>${color.name[lang]}</strong>`;
  $(".gallery-main").innerHTML =
    image(
      paths[imageIndex],
      `${p.name}, ${color.name[lang]}, ${t("prikaz", "view")} ${imageIndex + 1}`,
      "",
      true,
    ) +
    `<span class="gallery-count">${imageIndex + 1} / ${paths.length}</span>`;
  $(".gallery-thumbnails").innerHTML = paths
    .map(
      (path, i) =>
        `<button type="button" data-action="gallery" data-index="${i}" class="${i === imageIndex ? "selected" : ""}" aria-label="${t("Prikaz", "View")} ${i + 1}" aria-pressed="${i === imageIndex}">${image(path)}</button>`,
    )
    .join("");
  $$(".swatches.large .swatch").forEach((b) => {
    const selected = b.dataset.color === color.id;
    b.classList.toggle("selected", selected);
    b.setAttribute("aria-pressed", selected);
  });
}
function addToBag() {
  const { p, size, color } = productState;
  if (!size) {
    let error = $(".field-error");
    if (!error) {
      error = document.createElement("p");
      error.className = "field-error";
      error.setAttribute("role", "alert");
      $(".sizes").after(error);
    }
    error.textContent = t(
      "Odaberite veličinu prije dodavanja u korpu.",
      "Choose a size before adding to your bag.",
    );
    return;
  }
  const key = `${p.id}-${size}-${color.id}`,
    row = bag.find((x) => x.key === key);
  if (row) row.qty = Math.min(5, row.qty + 1);
  else bag.push({ key, id: p.id, size, color: color.id, qty: 1 });
  updateBag();
  openDialog("bag");
}

// Native dialogs provide keyboard support and restore focus after dismissal.
function openDialog(kind) {
  if (dialog.open) closeDialog();
  dialogKind = kind;
  dialogTrigger = document.activeElement;
  dialog.className = `modal ${["bag", "menu", "filters"].includes(kind) ? "side-drawer" : ""} ${kind === "menu" ? "mobile-nav-drawer" : ""} ${kind === "search" ? "search-modal" : ""}`;
  const titles = {
    bag: `${t("Vaša korpa", "Your bag")} (${bag.reduce((s, x) => s + x.qty, 0)})`,
    menu: t("Meni", "Menu"),
    search: t("Pronađite svoj model", "Find your next piece"),
    filters: t("Filteri", "Filters"),
    "size-guide": "Tabela mjera",
  };
  $(".modal-head h2", dialog).textContent = titles[kind];
  dialog.setAttribute("aria-label", titles[kind]);
  dialog.setAttribute("lang", kind === "size-guide" ? "bs" : lang);
  $('[data-action="close-dialog"]', dialog).setAttribute(
    "aria-label",
    kind === "size-guide" ? "Zatvori" : t("Zatvori", "Close"),
  );
  const content = $("#dialog-content");
  if (kind === "bag") renderBag();
  if (kind === "search") {
    content.innerHTML = template("search");
    updateSearch("");
  }
  if (kind === "menu")
    content.innerHTML = `<nav><a href="${pageUrl(`/shop`)}">${t("Sva odjeća", "Shop all")}</a><details><summary>${t("Kategorije", "Categories")}</summary>${CATEGORIES.map((c) => `<a href="${pageUrl(`/shop?category=${c.id}`)}">${c[lang]}</a>`).join("")}</details><a href="${pageUrl(`/company`)}">${t("O nama", "Our company")}</a><a href="${pageUrl(`/manufacturing`)}">${t("Proizvodnja", "Manufacturing")}</a><a href="${pageUrl(`/contact`)}">${t("Kontakt", "Contact")}</a><button class="language" type="button">${lang === "bs" ? "English" : "Bosanski"}</button></nav>`;
  if (kind === "filters") {
    content.innerHTML = template("filters");
    $$(".filter-fields select", content).forEach((s, i) => {
      s.dataset.filter = ["color", "size", "price"][i];
      s.value = route().params.get(s.dataset.filter) || "";
    });
    $(".filter-fields .button").dataset.action = "apply-filters";
    $(".filter-fields .underlined").dataset.action = "clear-filters";
    renderShop();
  }
  if (kind === "size-guide") {
    content.innerHTML = $("#size-guide").innerHTML;
    $("[data-measurement-chart]", content).innerHTML = measurementChart();
    $("[data-measurement-product]", content).textContent = `${productState.p.name} / ${productState.p.label.bs}`;
    $("[data-bust-instruction]", content).hidden = productState.p.type === "trousers";
  }
  dialog.showModal();
  document.body.style.overflow = "hidden";
  if (kind === "search") $("#search-input").focus();
}
function closeDialog() {
  if (dialog.open) dialog.close();
  document.body.style.overflow = "";
  if (dialogTrigger?.isConnected) dialogTrigger.focus();
  dialogKind = "";
}
dialog.addEventListener("cancel", (event) => {
  event.preventDefault();
  closeDialog();
});
dialog.addEventListener("click", (event) => {
  if (event.target === dialog) closeDialog();
});
dialog.addEventListener("keydown", (event) => {
  if (event.key !== "Tab") return;
  const controls = $$(
    'a[href],button,input,select,textarea,summary,[tabindex="0"]',
    dialog,
  ).filter((el) => !el.disabled && el.getClientRects().length);
  const first = controls[0],
    last = controls.at(-1);
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault();
    last?.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first?.focus();
  }
});
function renderBag() {
  const count = bag.reduce((sum, x) => sum + x.qty, 0);
  $(".modal-head h2", dialog).textContent =
    `${t("Vaša korpa", "Your bag")} (${count})`;
  if (!bag.length) {
    $("#dialog-content").innerHTML =
      `<div class="empty-state">${icon("bag", 40)}<h3>${t("Mjesto za vaš sljedeći komad.", "Room for your next piece.")}</h3><p>${t("Vaša korpa je trenutno prazna.", "Your bag is currently empty.")}</p><a href="${pageUrl(`/shop`)}" class="button">${t("Otkrijte kolekciju", "Explore the collection")}</a></div>`;
    return;
  }
  $("#dialog-content").innerHTML =
    `<div class="bag-panel"><p class="bag-confirmation">${icon("check", 16)}${t("Vaš izbor je sačuvan u korpi.", "Your selection is saved in your bag.")}</p><div class="bag-items">${bag
      .map((x) => {
        const p = PRODUCTS.find((p) => p.id === x.id),
          c = p.colors.find((c) => c.id === x.color);
        return `<article class="bag-item"><a href="${pageUrl(`/product/${p.id}`)}">${image(c.cover, p.name)}</a><div><div class="bag-item-title"><a href="${pageUrl(`/product/${p.id}`)}">${p.name}</a><button type="button" class="icon-button small" data-action="remove-item" data-key="${x.key}" aria-label="${t("Ukloni", "Remove")} ${p.name}">${icon("close", 17)}</button></div><p>${c.name[lang]} / ${t("Veličina", "Size")} ${x.size}</p><strong>${money(p.price * x.qty)}</strong><div class="quantity"><button type="button" data-action="quantity" data-key="${x.key}" data-delta="-1" aria-label="${t("Smanji količinu", "Decrease quantity")}" ${x.qty === 1 ? "disabled" : ""}>${icon("minus", 13)}</button><span>${x.qty}</span><button type="button" data-action="quantity" data-key="${x.key}" data-delta="1" aria-label="${t("Povećaj količinu", "Increase quantity")}" ${x.qty === 5 ? "disabled" : ""}>${icon("plus", 13)}</button></div></div></article>`;
      })
      .join(
        "",
      )}</div><div class="bag-total"><span>${t("Ukupno", "Subtotal")}</span><strong>${money(total())}</strong></div><p class="preview-helper">${t("Primjeri cijena. Završetak kupovine ne naplaćuje narudžbu.", "Sample prices. Checkout does not charge an order.")}</p><a class="button secondary" href="${pageUrl(`/cart`)}">${t("Pregledaj korpu", "View cart")}${icon("right", 17)}</a><a class="button" href="${pageUrl(`/checkout`)}">${t("Nastavi na pregled kupovine", "Continue to checkout preview")}${icon("right", 17)}</a><button type="button" class="underlined" data-action="close-dialog">${t("Nastavi razgledati", "Continue browsing")}</button></div>`;
}
function updateSearch(query) {
  const list = query
    ? PRODUCTS.filter((p) =>
        normalise(`${p.name} ${p.label.bs} ${p.label.en}`).includes(
          normalise(query),
        ),
      )
    : PRODUCTS.slice(0, 3);
  const live = $("[aria-live]", dialog);
  live.innerHTML =
    list
      .map(
        (p) =>
          `<a class="search-result" href="${pageUrl(`/product/${p.id}`)}">${image(p.colors[0].cover)}<span><strong>${p.name}</strong><span>${p.label[lang]}</span></span>${icon("arrow")}</a>`,
      )
      .join("") ||
    `<p>${t("Nema rezultata. Pokušajte „hlače“, „sako“ ili naziv modela.", "No results. Try “trousers”, “blazer” or a piece name.")}</p>`;
  $(".search-panel>.button", dialog).href =
    pageUrl(`/shop?q=${encodeURIComponent(query)}`);
}

// Contact messages are composed in the visitor's email app; delivery is up to them.
function prepareContactEmail(form) {
  for (const field of form.querySelectorAll("[required]")) {
    field.value = field.value.trim();
  }
  const message = form.elements.message;
  message.setCustomValidity(message.value.length < 10 ? t(
    "Napišite poruku od najmanje 10 znakova.",
    "Please write a message of at least 10 characters.",
  ) : "");
  if (!form.reportValidity()) return;
  const data = new FormData(form);
  const topic = form.elements.topic.selectedOptions[0].textContent;
  const body = [
    `${t("Ime i prezime", "Name")}: ${data.get("name")}`,
    `Email: ${data.get("email")}`,
    ...(data.get("phone")?.trim()
      ? [`${t("Telefon", "Phone")}: ${data.get("phone").trim()}`]
      : []),
    `${t("Tema", "Topic")}: ${topic}`,
    "",
    data.get("message"),
  ].join("\r\n");
  const mailto = `mailto:slovenka.dd@gmail.com?subject=${encodeURIComponent(`MK Slovenka — ${topic}`)}&body=${encodeURIComponent(body)}`;
  const link = $("[data-contact-email]", form);
  link.href = mailto;
  link.hidden = false;
  $("[data-contact-status]", form).textContent = t(
    "Poruka je pripremljena. Pošaljite je iz svoje aplikacije za email. Ako se aplikacija nije otvorila, koristite link ispod ili nam pišite na slovenka.dd@gmail.com.",
    "Your message is prepared. Send it from your email app. If the app did not open, use the link below or email slovenka.dd@gmail.com directly.",
  );
  // Keep the form intact if no email app is configured or the visitor cancels.
  location.href = mailto;
}

// Production inquiry previews are stored only on this device, without a server.
function saveSubmission(record) {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open("mk-slovenka-preview", 1);
    request.onupgradeneeded = () =>
      request.result.createObjectStore("submissions", { keyPath: "reference" });
    request.onerror = () => reject(request.error);
    request.onsuccess = () => {
      const db = request.result,
        transaction = db.transaction("submissions", "readwrite");
      transaction.objectStore("submissions").put(record);
      transaction.oncomplete = () => {
        db.close();
        resolve(record);
      };
      transaction.onerror = () => {
        db.close();
        reject(transaction.error);
      };
    };
  });
}
function newReference(kind) {
  return `MK-${kind}-${(crypto.randomUUID?.() || Math.random().toString(36).slice(2)).slice(0, 8).toUpperCase()}`;
}
function showFormError(form, message) {
  let error = $(".form-error", form);
  if (!error) {
    error = document.createElement("p");
    error.className = "form-error";
    error.setAttribute("role", "alert");
    $(".button", form).before(error);
  }
  error.textContent = message;
}
async function submitPreview(form, kind) {
  const button = $('button[type="submit"],button.button', form),
    original = button.innerHTML;
  const data = new FormData(form),
    file = data.get("attachment");
  if (file?.size > 10 * 1024 * 1024) {
    showFormError(
      form,
      t(
        "Datoteka mora biti manja od 10 MB.",
        "File must be smaller than 10 MB.",
      ),
    );
    return;
  }
  if (
    file?.size &&
    !["application/pdf", "image/jpeg", "image/png"].includes(file.type)
  ) {
    showFormError(
      form,
      t(
        "Odaberite PDF, JPG ili PNG datoteku.",
        "Choose a PDF, JPG or PNG file.",
      ),
    );
    return;
  }
  const fields = Object.fromEntries(data);
  fields.services = data.getAll("services");
  delete fields.attachment;
  const record = {
    reference:
      kind === "order"
        ? (checkoutRequestId ||= newReference("O"))
        : newReference(kind === "inquiry" ? "P" : "C"),
    kind,
    createdAt: new Date().toISOString(),
    fields,
  };
  if (file?.size)
    record.attachment = { name: file.name, type: file.type, file };
  if (kind === "order") {
    record.items = bag.map((x) => ({ ...x }));
    record.total = total() + 10;
  }
  button.disabled = true;
  button.textContent = t("Čuvanje…", "Saving…");
  $(".form-error", form)?.remove();
  try {
    await saveSubmission(record);
    if (kind === "order") {
      bag = [];
      updateBag();
      location.href = pageUrl("/order");
    } else {
      const inquiry = kind === "inquiry";
      form.outerHTML = `<div class="success-panel" role="status">${icon("check", 44)}<p class="eyebrow">${t("SAČUVANO U PREGLEDNIKU", "SAVED IN THIS BROWSER")}</p><h2>${inquiry ? t("Hvala što ste podijelili svoj projekat.", "Thank you for sharing your project.") : t("Poruka je sačuvana.", "Message saved.")}</h2><p>${t("Referenca", "Reference")}: <strong>${record.reference}</strong></p><p>${t("Podaci su sačuvani samo u ovom pregledniku. Poruka nije poslana e-mailom.", "Details are saved only in this browser. No email has been sent.")}</p><a class="text-link" href="${pageUrl(`/contact`)}">${t("Kontakt i podrška", "Contact & support")}${icon("arrow", 17)}</a><button class="button secondary" type="button" data-action="reset-form">${t("Novi upit", "Start a new inquiry")}</button></div>`;
    }
    $("#main").scrollIntoView();
  } catch {
    showFormError(
      form,
      t(
        "Podaci nisu sačuvani. Omogućite pohranu u pregledniku i pokušajte ponovo; uneseni podaci ostaju u obrascu.",
        "Details could not be saved. Enable browser storage and try again; your entered details remain in the form.",
      ),
    );
    button.disabled = false;
    button.innerHTML = original;
  }
}
// One delegated event handler keeps every page free of framework code.
document.addEventListener("click", (event) => {
  const link = event.target.closest("a[href]");
  if (link?.getAttribute("href") === "#main") {
    event.preventDefault();
    $("#main").setAttribute("tabindex", "-1");
    $("#main").focus();
    $("#main").scrollIntoView();
    return;
  }
  const button = event.target.closest("button");
  if (!button) return;
  if (button.matches(".language")) {
    const nextLanguage = lang === "bs" ? "en" : "bs";
    write("slovenka-static-language", nextLanguage);
    location.href = pageUrl(`${route().path}${location.search}${location.hash}`, nextLanguage);
    return;
  }
  if (button.matches(".mobile-menu")) return openDialog("menu");
  if (button.matches(".utilities .icon-button")) return openDialog("search");
  if (button.matches(".bag-trigger")) return openDialog("bag");
  if (button.matches(".nav-button")) {
    const menu = $("#shop-menu");
    if (menu) {
      menu.remove();
      button.setAttribute("aria-expanded", "false");
      button.removeAttribute("aria-controls");
    } else {
      $("header").insertAdjacentHTML("beforeend", template("shopMenu"));
      button.setAttribute("aria-expanded", "true");
      button.setAttribute("aria-controls", "shop-menu");
      $(".shop-menu").id = "shop-menu";
    }
    return;
  }
  if (button.matches(".menu-close")) {
    $(".shop-menu")?.remove();
    $(".nav-button").setAttribute("aria-expanded", "false");
    $(".nav-button").removeAttribute("aria-controls");
    return;
  }
  const action = button.dataset.action;
  if (action === "close-dialog" || action === "apply-filters")
    return closeDialog();
  if (action === "filters" || action === "size-guide")
    return openDialog(action);
  if (action === "clear-filters") {
    const category = route().params.get("category");
    location.href = pageUrl(`/shop${category ? "?category=" + category : ""}`);
    return;
  }
  if (action === "card-color") {
    const p = PRODUCTS.find((p) => p.id === button.dataset.product);
    button.closest(".product-card").outerHTML = productCard(
      p,
      button.dataset.color,
    );
    return;
  }
  if (action === "product-color") {
    productState.color = productState.p.colors.find(
      (c) => c.id === button.dataset.color,
    );
    productState.imageIndex = 0;
    updateGallery();
    return;
  }
  if (action === "gallery") {
    productState.imageIndex = Number(button.dataset.index);
    updateGallery();
    return;
  }
  if (action === "size") {
    productState.size = button.dataset.size;
    $$(".sizes button").forEach((b) => {
      b.classList.toggle("selected", b === button);
      b.setAttribute("aria-pressed", b === button);
    });
    $(".field-error")?.remove();
    return;
  }
  if (action === "add-to-bag") return addToBag();
  if (action === "quantity") {
    const row = bag.find((x) => x.key === button.dataset.key);
    row.qty = Math.max(1, Math.min(5, row.qty + Number(button.dataset.delta)));
    updateBag();
    renderBag();
    return;
  }
  if (action === "remove-item") {
    bag = bag.filter((x) => x.key !== button.dataset.key);
    updateBag();
    renderBag();
    return;
  }
  if (action === "reset-form") return location.reload();
});
document.addEventListener("change", (event) => {
  const element = event.target;
  if (element.dataset.filter)
    return changeFilter(element.dataset.filter, element.value);
  if (element.name === "timing") {
    const field = $('[name="targetDate"]');
    if (element.value === "target" && !field) {
      const label = document.createElement("label");
      label.className = "form-field";
      label.innerHTML = `${t("Okvirni datum", "Target date")}<input type="date" name="targetDate" required>`;
      element.closest("fieldset").append(label);
    } else if (element.value === "flexible") field?.closest("label").remove();
  }
  if (element.type === "file") {
    $(".upload-field strong").textContent =
      element.files[0]?.name ||
      t(
        "Dodajte tehnički paket (opcionalno)",
        "Add a technical pack (optional)",
      );
  }
});
document.addEventListener("input", (event) => {
  if (event.target.id === "search-input") updateSearch(event.target.value);
  const contactForm = event.target.closest("[data-contact-form]");
  if (contactForm) {
    event.target.setCustomValidity?.("");
    const link = $("[data-contact-email]", contactForm);
    link.hidden = true;
    link.removeAttribute("href");
    $("[data-contact-status]", contactForm).textContent = "";
  }
});
document.addEventListener("submit", (event) => {
  if (event.target.matches("[data-commerce-form]")) return;
  if (event.target.matches("[data-contact-form]")) {
    event.preventDefault();
    prepareContactEmail(event.target);
    return;
  }
  if (!event.target.closest(".search-panel") && !["/inquiry", "/contact"].includes(route().path)) return;
  event.preventDefault();
  if (event.target.closest(".search-panel"))
    return navigate(`/shop?q=${encodeURIComponent($("#search-input").value)}`);
  const path = route().path;
  if (["/inquiry", "/contact", "/checkout"].includes(path))
    submitPreview(
      event.target,
      path === "/inquiry"
        ? "inquiry"
        : path === "/contact"
          ? "contact"
          : "order",
    );
});
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && !dialog.open) {
    $(".shop-menu")?.remove();
    $(".nav-button").setAttribute("aria-expanded", "false");
    $(".nav-button").removeAttribute("aria-controls");
  }
});
// Shared commerce adapter; commerce.js handles cart, checkout and received-order pages.
window.SlovenkaCommerce = {
  PRODUCTS, lang, money, t, image, read, write, pageUrl,
  getBag: () => bag.map((row) => ({ ...row })),
  setBag: (items) => { bag = items; updateBag(); },
};
updateBag();
if (route().path === "/shop") setupShop();
if (route().path.startsWith("/product/")) setupProduct(route().path.split("/").pop(), route().params.get("color"));
if (route().path === "/inquiry") {
  const date = new Date();
  const today = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
  const field = $('#main [name="targetDate"]');
  if (field) field.min = today;
}


// Language links preserve the current native search and fragment when available.
$$("a.language").forEach((link) => {
  link.href = pageUrl(`${route().path}${location.search}${location.hash}`, lang === "bs" ? "en" : "bs");
});
