using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;
using System.Linq.Expressions;

namespace Sigav.Infrastructure.Repositories;

public class EfRepository<T> : IRepository<T> where T : BaseEntity
{
    protected readonly SigavDbContext _context;
    protected readonly DbSet<T> _dbSet;

    public EfRepository(SigavDbContext context)
    {
        _context = context;
        _dbSet = context.Set<T>();
    }

    public virtual async Task<T?> GetByIdAsync(int id)
    {
        return await _dbSet.FindAsync(id);
    }

    public virtual async Task<IEnumerable<T>> GetAllAsync()
    {
        return await _dbSet.Where(e => e.Activo).ToListAsync();
    }

    public virtual async Task<IEnumerable<T>> FindAsync(Expression<Func<T, bool>> predicate)
    {
        return await _dbSet.Where(predicate).Where(e => e.Activo).ToListAsync();
    }

    public virtual async Task<T> AddAsync(T entity)
    {
        entity.FechaCreacion = DateTime.UtcNow;
        entity.Activo = true;
        
        await _dbSet.AddAsync(entity);
        await _context.SaveChangesAsync();
        
        return entity;
    }

    public virtual async Task UpdateAsync(T entity)
    {
        entity.FechaActualizacion = DateTime.UtcNow;
        
        _dbSet.Update(entity);
        await _context.SaveChangesAsync();
    }

    public virtual async Task DeleteAsync(int id)
    {
        var entity = await GetByIdAsync(id);
        if (entity != null)
        {
            entity.Activo = false;
            entity.FechaActualizacion = DateTime.UtcNow;
            await _context.SaveChangesAsync();
        }
    }

    public virtual async Task<bool> ExistsAsync(int id)
    {
        return await _dbSet.AnyAsync(e => e.Id == id && e.Activo);
    }

    public virtual async Task<int> CountAsync(Expression<Func<T, bool>>? predicate = null)
    {
        var query = _dbSet.Where(e => e.Activo);
        
        if (predicate != null)
            query = query.Where(predicate);
            
        return await query.CountAsync();
    }
}
