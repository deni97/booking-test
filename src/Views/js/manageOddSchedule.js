let deleteButtons = document.querySelectorAll(".deleteBtn")

deleteButtons.forEach(deleteBtn => {
    deleteBtn.href = deleteBtn.href.replace(/-/g, "")
})
