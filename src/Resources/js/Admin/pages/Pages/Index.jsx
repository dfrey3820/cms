import React from 'react';
import { Link } from '@inertiajs/react';

export default function Index({ pages }) {
    return (
        <div>
            <h1>Pages</h1>
            <Link href="/admin/pages/create">Create Page</Link>
            <ul>
                {pages.map(page => (
                    <li key={page.id}>
                        <Link href={`/admin/pages/${page.id}/edit`}>{page.title}</Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}