import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 40 40"
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path d="M20 3.5 34 9v9.5c0 8.6-5.8 14.6-14 18-8.2-3.4-14-9.4-14-18V9l14-5.5Z" />
            <path d="M11 16.3c3.4-.8 6.4.1 9 2.2 2.6-2.1 5.6-3 9-2.2v9.5c-3.4-.8-6.4.1-9 2.2-2.6-2.1-5.6-3-9-2.2v-9.5Z" />
            <path d="M20 18.5v9.5M15 20.5h2.5m5 0H25M15 23.5h2.5m5 0H25" />
        </svg>
    );
}
