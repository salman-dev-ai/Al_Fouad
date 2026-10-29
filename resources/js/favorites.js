// وحدة المفضلة في المتصفح: تحفظ مفضلة الزائر محليًا، وتعكس مفضلة المسجّل القادمة من الخادم.
// تعرض واجهة بسيطة على window.Favorites (has / count / all / toggle) لا تعتمد على Alpine.
// أي تغيير يُطلق الحدث favorites-changed فتتحدث كل الأزرار والعدّاد فورًا.
const STORAGE_KEY = 'fouad:favorites';
const MAX_LOCAL = 100;

// يقرأ مفضلة الزائر من التخزين المحلي بحذر: أي بيانات تالفة أو غير رقمية تُتجاهل.
const readLocal = () => {
    try {
        const value = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]');

        return Array.isArray(value)
            ? value.map(Number).filter(Number.isInteger).slice(0, MAX_LOCAL)
            : [];
    } catch {
        return [];
    }
};

// يكتب المفضلة محليًا (حدّ أقصى 100)، ويتجاهل الفشل (وضع التصفح الخاص أو امتلاء التخزين).
const writeLocal = (ids) => {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(ids.slice(0, MAX_LOCAL)));
    } catch {
        // لا شيء: الواجهة تبقى تعمل في الذاكرة لهذه الجلسة
    }
};

const state = { authed: false, wire: null, ids: new Set(readLocal()) };

const emit = () => window.dispatchEvent(new CustomEvent('favorites-changed'));

window.Favorites = {
    has: (id) => state.ids.has(Number(id)),
    count: () => state.ids.size,
    all: () => [...state.ids],

    // يُستدعى من مكوّن المزامنة مع كل تحميل صفحة: المسجّل يأخذ مفضلته من الخادم ويدمج ما حفظه محليًا (مرة واحدة)،
    // والزائر يقرأ مفضلته المحلية. بعد الدمج يُمسح التخزين المحلي فلا يتكرر الدمج.
    // أي فشل في الدمج يُبقي البيانات المحلية لتُجرَّب لاحقًا.
    boot({ authed, ids, wire }) {
        state.authed = authed;
        state.wire = wire;

        if (authed) {
            state.ids = new Set(ids.map(Number));

            const local = readLocal();

            if (local.length > 0) {
                Promise.resolve(wire.merge(local))
                    .then(() => writeLocal([]))
                    .catch(() => {});
            }
        } else {
            state.ids = new Set(readLocal());
        }

        emit();
    },

    // يعتمد القائمة الجديدة القادمة من الخادم (بعد كل حفظ أو دمج) للمسجّل فقط.
    sync(ids) {
        if (state.authed) {
            state.ids = new Set([...ids].map(Number));
            emit();
        }
    },

    // يبدّل مفضلة منتج: يغيّر الواجهة فورًا، ثم يحفظ في الخادم (للمسجّل) أو محليًا (للزائر).
    // إن فشل حفظ الخادم تُعاد الحالة السابقة، ويؤكد الخادم القائمة النهائية في كل الأحوال.
    toggle(id) {
        id = Number(id);

        const on = !state.ids.has(id);
        on ? state.ids.add(id) : state.ids.delete(id);
        emit();

        if (state.authed && state.wire) {
            Promise.resolve(state.wire.setFavorite(id, on)).catch(() => {
                on ? state.ids.delete(id) : state.ids.add(id);
                emit();
            });
        } else {
            writeLocal([...state.ids]);
        }
    },
};