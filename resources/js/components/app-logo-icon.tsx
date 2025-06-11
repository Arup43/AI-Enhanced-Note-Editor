import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 42" xmlns="http://www.w3.org/2000/svg">
            <g>
                {/* Document/Paper background */}
                <rect x="6" y="4" width="28" height="34" rx="2" ry="2" fill="currentColor" opacity="0.1" stroke="currentColor" strokeWidth="1.5"/>
                
                {/* Spiral binding holes */}
                <circle cx="10" cy="8" r="1" fill="currentColor" opacity="0.3"/>
                <circle cx="10" cy="12" r="1" fill="currentColor" opacity="0.3"/>
                <circle cx="10" cy="16" r="1" fill="currentColor" opacity="0.3"/>
                <circle cx="10" cy="20" r="1" fill="currentColor" opacity="0.3"/>
                <circle cx="10" cy="24" r="1" fill="currentColor" opacity="0.3"/>
                <circle cx="10" cy="28" r="1" fill="currentColor" opacity="0.3"/>
                <circle cx="10" cy="32" r="1" fill="currentColor" opacity="0.3"/>
                
                {/* Text lines */}
                <line x1="14" y1="10" x2="30" y2="10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                <line x1="14" y1="14" x2="28" y2="14" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                <line x1="14" y1="18" x2="30" y2="18" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                <line x1="14" y1="22" x2="26" y2="22" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                <line x1="14" y1="26" x2="29" y2="26" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                <line x1="14" y1="30" x2="25" y2="30" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                
                {/* Pen/Pencil */}
                <path d="M32 2L36 6L24 18L20 16L32 2Z" fill="currentColor" opacity="0.8"/>
                <path d="M20 16L22 20L24 18L20 16Z" fill="currentColor" opacity="0.6"/>
            </g>
        </svg>
    );
}
