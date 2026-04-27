export type CopyStatus =
    | 'available'
    | 'borrowed'
    | 'lost'
    | 'damaged'
    | 'withdrawn';

export type Option = {
    value: string;
    label: string;
};

export type Category = {
    id: number;
    name: string;
    description: string | null;
    books_count?: number;
};

export type Book = {
    id: number;
    category_id: number;
    title: string;
    author: string;
    isbn: string | null;
    publisher: string | null;
    published_year: number | null;
    language: string | null;
    pages: number | null;
    description: string | null;
    category?: Pick<Category, 'id' | 'name'>;
    total_copies_count?: number;
    available_copies_count?: number;
};

export type BookSummary = Pick<Book, 'id' | 'title' | 'author'>;

export type Member = {
    id: number;
    member_no: string;
    name: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    note: string | null;
    is_active: boolean;
    open_loans_count?: number;
    overdue_loans_count?: number;
};

export type MemberSummary = Pick<Member, 'id' | 'member_no' | 'name'> &
    Partial<Pick<Member, 'phone' | 'is_active' | 'open_loans_count'>>;

export type BookCopy = {
    id: number;
    book_id: number;
    inventory_no: string;
    status: CopyStatus;
    shelf: string | null;
    acquired_at: string | null;
    note: string | null;
    book?: BookSummary;
    open_loan?: (Loan & { member: MemberSummary }) | null;
};

export type UserSummary = {
    id: number;
    name: string;
};

export type Loan = {
    id: number;
    book_copy_id: number;
    member_id: number;
    issued_by: number | null;
    returned_by: number | null;
    /** "YYYY-MM-DD HH:mm" in library time */
    borrowed_at: string;
    /** "YYYY-MM-DD" */
    due_at: string;
    returned_at: string | null;
    renewals: number;
    note: string | null;
    is_overdue: boolean;
    days_overdue: number;
    copy?: Pick<BookCopy, 'id' | 'inventory_no'> &
        Partial<Pick<BookCopy, 'book_id' | 'status'>> & {
            book?: BookSummary;
        };
    member?: MemberSummary;
    issuer?: UserSummary | null;
    receiver?: UserSummary | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};
