import tkinter as tk
from tkinter import ttk

class ERPGuiSampler(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title("ERP System Sampler")
        self.geometry("800x500")
        self.resizable(False, False)

        self.tabs = ttk.Notebook(self)
        self.tabs.pack(fill='both', expand=True)

        self.create_transactions_tab()
        self.create_suppliers_tab()
        self.create_purchase_orders_tab()
        self.create_deliveries_tab()
        self.create_analytics_tab()

    def create_transactions_tab(self):
        frame = ttk.Frame(self.tabs)
        self.tabs.add(frame, text="Transactions")
        cols = ("Date", "Material", "Product Used", "Type", "Quantity", "Location", "Balance")
        tree = ttk.Treeview(frame, columns=cols, show='headings')
        for col in cols:
            tree.heading(col, text=col)
        tree.pack(fill='both', expand=True)
        # Sample data
        tree.insert('', 'end', values=("2024-06-01", "Polymer A", "Bag", "IN", "100", "Warehouse 1", "500"))
        tree.insert('', 'end', values=("2024-06-02", "Polymer B", "Bag", "OUT", "50", "Warehouse 2", "450"))

    def create_suppliers_tab(self):
        frame = ttk.Frame(self.tabs)
        self.tabs.add(frame, text="Suppliers")
        cols = ("Supplier Name", "Contact", "Email", "Phone", "Rating", "Orders", "Total Spent", "Status")
        tree = ttk.Treeview(frame, columns=cols, show='headings')
        for col in cols:
            tree.heading(col, text=col)
        tree.pack(fill='both', expand=True)
        tree.insert('', 'end', values=("ABC Polymers", "John Doe", "abc@poly.com", "1234567890", "5", "45", "₱100,000", "Active"))
        tree.insert('', 'end', values=("XYZ Chemicals", "Jane Smith", "xyz@chem.com", "0987654321", "4", "30", "₱75,000", "Inactive"))

    def create_purchase_orders_tab(self):
        frame = ttk.Frame(self.tabs)
        self.tabs.add(frame, text="Purchase Orders")
        cols = ("Order #", "Supplier", "Order Date", "Expected Delivery", "Items", "Total Amount", "Status")
        tree = ttk.Treeview(frame, columns=cols, show='headings')
        for col in cols:
            tree.heading(col, text=col)
        tree.pack(fill='both', expand=True)
        tree.insert('', 'end', values=("PO-001", "ABC Polymers", "2024-06-01", "2024-06-10", "5", "₱50,000", "Processing"))
        tree.insert('', 'end', values=("PO-002", "XYZ Chemicals", "2024-06-02", "2024-06-12", "3", "₱30,000", "Delivered"))

    def create_deliveries_tab(self):
        frame = ttk.Frame(self.tabs)
        self.tabs.add(frame, text="Deliveries")
        cols = ("Delivery #", "PO Number", "Supplier", "Delivery Date", "Status", "Notes")
        tree = ttk.Treeview(frame, columns=cols, show='headings')
        for col in cols:
            tree.heading(col, text=col)
        tree.pack(fill='both', expand=True)
        tree.insert('', 'end', values=("D-001", "PO-001", "ABC Polymers", "2024-06-11", "Delivered", "On time"))
        tree.insert('', 'end', values=("D-002", "PO-002", "XYZ Chemicals", "2024-06-13", "Pending", "Delayed"))

    def create_analytics_tab(self):
        frame = ttk.Frame(self.tabs)
        self.tabs.add(frame, text="Analytics")
        lbl = tk.Label(frame, text="Analytics Overview\n\nTop Supplier: ABC Polymers\nOn-Time Delivery: 87.5%\nCost Savings: ₱125,000\nQuality Score: 4.2/5.0", font=("Arial", 14), justify="left")
        lbl.pack(padx=20, pady=20, anchor="nw")

if __name__ == "__main__":
    app = ERPGuiSampler()
    app.mainloop()
