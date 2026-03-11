import { useMemo, useState } from 'react';

export function usePOSCart() {
  const [items, setItems] = useState([]);
  const total = useMemo(() => items.reduce((sum, item) => sum + item.price, 0), [items]);
  return { items, setItems, total };
}
