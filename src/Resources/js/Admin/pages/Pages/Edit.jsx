import React from 'react';
import { useForm } from '@inertiajs/react';

export default function Edit({ page }) {
    const { data, setData, put, errors } = useForm({
        title: page.title,
        slug: page.slug,
        content: page.content,
        blocks: page.blocks || [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/admin/pages/${page.id}`);
    };

    return (
        <form onSubmit={handleSubmit}>
            <input
                type="text"
                value={data.title}
                onChange={e => setData('title', e.target.value)}
                placeholder="Title"
            />
            {errors.title && <div>{errors.title}</div>}

            <input
                type="text"
                value={data.slug}
                onChange={e => setData('slug', e.target.value)}
                placeholder="Slug"
            />
            {errors.slug && <div>{errors.slug}</div>}

            <textarea
                value={data.content}
                onChange={e => setData('content', e.target.value)}
                placeholder="Content"
            />

            <button type="submit">Update Page</button>
        </form>
    );
}