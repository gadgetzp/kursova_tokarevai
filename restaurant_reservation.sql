--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.4

-- Started on 2026-05-14 21:33:31

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 222 (class 1259 OID 18125)
-- Name: reservation_statuses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reservation_statuses (
    id integer NOT NULL,
    code character varying(30) NOT NULL,
    name character varying(100) NOT NULL
);


ALTER TABLE public.reservation_statuses OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 18124)
-- Name: reservation_statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.reservation_statuses_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reservation_statuses_id_seq OWNER TO postgres;

--
-- TOC entry 4856 (class 0 OID 0)
-- Dependencies: 221
-- Name: reservation_statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reservation_statuses_id_seq OWNED BY public.reservation_statuses.id;


--
-- TOC entry 226 (class 1259 OID 18150)
-- Name: reservations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reservations (
    id integer NOT NULL,
    user_id integer,
    table_id integer NOT NULL,
    status_id integer NOT NULL,
    customer_name character varying(100) NOT NULL,
    phone character varying(30) NOT NULL,
    email character varying(120),
    guests integer NOT NULL,
    reservation_date date NOT NULL,
    reservation_time time without time zone NOT NULL,
    comment text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    duration_hours integer DEFAULT 2 NOT NULL,
    CONSTRAINT reservations_guests_check CHECK ((guests > 0))
);


ALTER TABLE public.reservations OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 18149)
-- Name: reservations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.reservations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reservations_id_seq OWNER TO postgres;

--
-- TOC entry 4857 (class 0 OID 0)
-- Dependencies: 225
-- Name: reservations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reservations_id_seq OWNED BY public.reservations.id;


--
-- TOC entry 224 (class 1259 OID 18134)
-- Name: restaurant_tables; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.restaurant_tables (
    id integer NOT NULL,
    table_number character varying(20) NOT NULL,
    seats integer NOT NULL,
    zone_id integer NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    CONSTRAINT restaurant_tables_seats_check CHECK ((seats > 0))
);


ALTER TABLE public.restaurant_tables OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 18133)
-- Name: restaurant_tables_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.restaurant_tables_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.restaurant_tables_id_seq OWNER TO postgres;

--
-- TOC entry 4858 (class 0 OID 0)
-- Dependencies: 223
-- Name: restaurant_tables_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.restaurant_tables_id_seq OWNED BY public.restaurant_tables.id;


--
-- TOC entry 220 (class 1259 OID 18114)
-- Name: restaurant_zones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.restaurant_zones (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    description text
);


ALTER TABLE public.restaurant_zones OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 18113)
-- Name: restaurant_zones_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.restaurant_zones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.restaurant_zones_id_seq OWNER TO postgres;

--
-- TOC entry 4859 (class 0 OID 0)
-- Dependencies: 219
-- Name: restaurant_zones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.restaurant_zones_id_seq OWNED BY public.restaurant_zones.id;


--
-- TOC entry 218 (class 1259 OID 18100)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    email character varying(120) NOT NULL,
    phone character varying(30),
    password_hash character varying(255),
    role character varying(20) DEFAULT 'client'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT valid_user_role CHECK (((role)::text = ANY ((ARRAY['client'::character varying, 'admin'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 217 (class 1259 OID 18099)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- TOC entry 4860 (class 0 OID 0)
-- Dependencies: 217
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 4665 (class 2604 OID 18128)
-- Name: reservation_statuses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservation_statuses ALTER COLUMN id SET DEFAULT nextval('public.reservation_statuses_id_seq'::regclass);


--
-- TOC entry 4668 (class 2604 OID 18153)
-- Name: reservations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations ALTER COLUMN id SET DEFAULT nextval('public.reservations_id_seq'::regclass);


--
-- TOC entry 4666 (class 2604 OID 18137)
-- Name: restaurant_tables id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.restaurant_tables ALTER COLUMN id SET DEFAULT nextval('public.restaurant_tables_id_seq'::regclass);


--
-- TOC entry 4664 (class 2604 OID 18117)
-- Name: restaurant_zones id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.restaurant_zones ALTER COLUMN id SET DEFAULT nextval('public.restaurant_zones_id_seq'::regclass);


--
-- TOC entry 4661 (class 2604 OID 18103)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 4846 (class 0 OID 18125)
-- Dependencies: 222
-- Data for Name: reservation_statuses; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reservation_statuses (id, code, name) FROM stdin;
1	new	Нове
2	confirmed	Підтверджене
3	cancelled	Скасоване
4	completed	Завершене
\.


--
-- TOC entry 4850 (class 0 OID 18150)
-- Dependencies: 226
-- Data for Name: reservations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reservations (id, user_id, table_id, status_id, customer_name, phone, email, guests, reservation_date, reservation_time, comment, created_at, duration_hours) FROM stdin;
3	\N	1	3	Андрій	+380000000000	123@gmail.com	2	2026-05-09	12:00:00	1 година, може 1,5	2026-05-08 23:28:19.218899	1
5	\N	2	1	тест	3783	14627@gmail.com	2	2026-05-13	17:00:00	\N	2026-05-09 00:04:34.98599	3
1	\N	6	3	test	+380909099090	test1@gmail.com	6	2026-05-15	19:52:00	\N	2026-05-08 18:49:27.723683	2
4	\N	4	1	Микола	+380998887766	myk@gmail.com	4	2026-05-09	12:30:00	\N	2026-05-08 23:29:43.319656	2
2	\N	6	2	test1	+380000000000	1@gmail.com	8	2026-05-12	19:52:00	\N	2026-05-08 18:51:27.605388	2
6	\N	9	4	тест	+380987654321	14627@gmail.com	2	2026-05-12	12:00:00	\N	2026-05-09 01:29:48.596009	3
8	\N	12	2	Софія	0912345678	sof@gmail.com	5	2026-05-23	12:30:00	\N	2026-05-09 02:30:59.989429	2
9	\N	8	4	Андрій	0982223344	ex@gmail.com	3	2026-05-19	14:00:00	\N	2026-05-09 18:01:23.823817	2
10	\N	1	1	Кирило	+380365428763	qwwe@gmail.com	2	2026-05-12	19:00:00	\N	2026-05-10 23:50:02.076257	2
7	\N	1	3	new	0990000000	new@gmail.com	2	2026-05-29	12:30:00	\N	2026-05-09 02:01:49.395057	4
\.


--
-- TOC entry 4848 (class 0 OID 18134)
-- Dependencies: 224
-- Data for Name: restaurant_tables; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.restaurant_tables (id, table_number, seats, zone_id, is_active) FROM stdin;
1	T1	2	2	t
2	T2	2	1	t
3	T3	4	1	t
4	T4	4	3	t
5	T5	6	4	t
6	T6	8	5	t
7	T7	2	1	t
8	T8	4	1	t
9	T9	2	2	t
10	T10	4	3	t
11	T11	6	5	t
12	T12	8	4	t
\.


--
-- TOC entry 4844 (class 0 OID 18114)
-- Dependencies: 220
-- Data for Name: restaurant_zones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.restaurant_zones (id, name, description) FROM stdin;
1	Основна зала	Головна зона ресторану для більшості гостей
2	Біля вікна	Столики з видом біля вікна
3	Біля бару	Столики поруч із барною зоною
4	VIP-зона	Окрема зона для приватного відпочинку
5	Тераса	Зона на відкритому повітрі
\.


--
-- TOC entry 4842 (class 0 OID 18100)
-- Dependencies: 218
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, name, email, phone, password_hash, role, created_at) FROM stdin;
1	Адміністратор	admin@gmail.com	+380000000000	admin123	admin	2026-05-08 18:32:26.317425
\.


--
-- TOC entry 4861 (class 0 OID 0)
-- Dependencies: 221
-- Name: reservation_statuses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reservation_statuses_id_seq', 4, true);


--
-- TOC entry 4862 (class 0 OID 0)
-- Dependencies: 225
-- Name: reservations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reservations_id_seq', 10, true);


--
-- TOC entry 4863 (class 0 OID 0)
-- Dependencies: 223
-- Name: restaurant_tables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.restaurant_tables_id_seq', 12, true);


--
-- TOC entry 4864 (class 0 OID 0)
-- Dependencies: 219
-- Name: restaurant_zones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.restaurant_zones_id_seq', 5, true);


--
-- TOC entry 4865 (class 0 OID 0)
-- Dependencies: 217
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- TOC entry 4683 (class 2606 OID 18132)
-- Name: reservation_statuses reservation_statuses_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservation_statuses
    ADD CONSTRAINT reservation_statuses_code_key UNIQUE (code);


--
-- TOC entry 4685 (class 2606 OID 18130)
-- Name: reservation_statuses reservation_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservation_statuses
    ADD CONSTRAINT reservation_statuses_pkey PRIMARY KEY (id);


--
-- TOC entry 4691 (class 2606 OID 18159)
-- Name: reservations reservations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_pkey PRIMARY KEY (id);


--
-- TOC entry 4687 (class 2606 OID 18141)
-- Name: restaurant_tables restaurant_tables_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.restaurant_tables
    ADD CONSTRAINT restaurant_tables_pkey PRIMARY KEY (id);


--
-- TOC entry 4689 (class 2606 OID 18143)
-- Name: restaurant_tables restaurant_tables_table_number_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.restaurant_tables
    ADD CONSTRAINT restaurant_tables_table_number_key UNIQUE (table_number);


--
-- TOC entry 4679 (class 2606 OID 18123)
-- Name: restaurant_zones restaurant_zones_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.restaurant_zones
    ADD CONSTRAINT restaurant_zones_name_key UNIQUE (name);


--
-- TOC entry 4681 (class 2606 OID 18121)
-- Name: restaurant_zones restaurant_zones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.restaurant_zones
    ADD CONSTRAINT restaurant_zones_pkey PRIMARY KEY (id);


--
-- TOC entry 4675 (class 2606 OID 18112)
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- TOC entry 4677 (class 2606 OID 18110)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 4693 (class 2606 OID 18170)
-- Name: reservations reservations_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_status_id_fkey FOREIGN KEY (status_id) REFERENCES public.reservation_statuses(id);


--
-- TOC entry 4694 (class 2606 OID 18165)
-- Name: reservations reservations_table_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_table_id_fkey FOREIGN KEY (table_id) REFERENCES public.restaurant_tables(id) ON DELETE CASCADE;


--
-- TOC entry 4695 (class 2606 OID 18160)
-- Name: reservations reservations_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- TOC entry 4692 (class 2606 OID 18144)
-- Name: restaurant_tables restaurant_tables_zone_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.restaurant_tables
    ADD CONSTRAINT restaurant_tables_zone_id_fkey FOREIGN KEY (zone_id) REFERENCES public.restaurant_zones(id);


-- Completed on 2026-05-14 21:33:31

--
-- PostgreSQL database dump complete
--

