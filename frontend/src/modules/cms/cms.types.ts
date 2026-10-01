export interface CMSBanner {
  id: string;
  title: string;
  subtitle: string;
  badgeText: string;
  ctaText: string;
  ctaLink: string;
  imageUrl: string;
  isActive: boolean;
}

export interface CMSStaticPages {
  about: string;
  terms: string;
  privacy: string;
  faq: string;
  [key: string]: string;
}

export interface CMSBlogPost {
  id: string;
  title: string;
  category: string;
  author: string;
  date: string;
  status: string;
  summary: string;
  content?: string;
}

export interface CMSPayload {
  banners: CMSBanner[];
  pages: CMSStaticPages;
  blogs: CMSBlogPost[];
}
